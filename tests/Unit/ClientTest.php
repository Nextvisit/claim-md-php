<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;
use Nextvisit\ClaimMD\Client;
use Nextvisit\ClaimMD\Config;
use Nextvisit\ClaimMD\Exceptions\ApiException;
use Nextvisit\ClaimMD\Exceptions\AuthenticationException;
use Nextvisit\ClaimMD\Exceptions\InvalidResponseException;
use Nextvisit\ClaimMD\Exceptions\NotFoundException;
use Nextvisit\ClaimMD\Exceptions\RateLimitException;
use Nextvisit\ClaimMD\Exceptions\ServerException;
use Nextvisit\ClaimMD\Requests\EligibilityRequest;
use Nextvisit\ClaimMD\Requests\FileRequest;

describe('Client', function () {
    it('creates a client with account key and config', function () {
        $client = new Client('test-account-key');

        expect($client)->toBeInstanceOf(Client::class);
    });

    it('creates a client with custom config', function () {
        $config = new Config();
        $client = new Client('test-account-key', $config);

        expect($client)->toBeInstanceOf(Client::class);
    });

    it('sends a POST request with account key in form data', function () {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'success'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('test-account-key', new Config(), $guzzleClient);

        $result = $client->sendRequest('POST', '/test-endpoint', ['data' => 'value']);

        expect($result)->toBe(['status' => 'success']);
        expect($container)->toHaveCount(1);

        $request = $container[0]['request'];
        expect($request->getMethod())->toBe('POST');
        expect((string) $request->getUri())->toBe('/test-endpoint');

        $body = (string) $request->getBody();
        expect($body)->toContain('AccountKey=test-account-key');
        expect($body)->toContain('data=value');
    });

    it('sends upload boundaries matching the request body', function (string $requestClass, string $method, string $endpoint) {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['uploaded' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('test-account-key', new Config(), $guzzleClient);

        $file = fopen('php://temp', 'w+');
        fwrite($file, 'synthetic X12 upload');
        rewind($file);

        try {
            $result = (new $requestClass($client))->$method($file);
            expect($result)->toBe(['uploaded' => true]);

            $request = $container[0]['request'];
            expect($request->getUri()->getPath())->toBe($endpoint);
            expect($request->getHeaderLine('Content-Type'))->toContain('multipart/form-data');
            expect(preg_match('/boundary=([^;]+)/', $request->getHeaderLine('Content-Type'), $matches))->toBe(1);
            $boundary = trim($matches[1], '"');
            expect((string) $request->getBody())
                ->toStartWith("--{$boundary}\r\n")
                ->toContain("\r\n--{$boundary}--\r\n")
                ->toContain('name="File"')
                ->toContain('synthetic X12 upload')
                ->toContain('name="AccountKey"')
                ->toContain('test-account-key');
        } finally {
            if (is_resource($file)) {
                fclose($file);
            }
        }
    })->with([
        'batch upload' => [FileRequest::class, 'upload', '/services/upload/'],
        '270 eligibility' => [EligibilityRequest::class, 'checkEligibility270271', '/services/elig/'],
    ]);

    it('includes additional headers when provided', function () {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => 'test'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('test-account-key', new Config(), $guzzleClient);

        $result = $client->sendRequest('POST', '/test', [], false, ['X-Custom-Header' => 'custom-value']);

        $request = $container[0]['request'];
        expect($request->getHeaderLine('X-Custom-Header'))->toBe('custom-value');
    });

    it('sends request without extra data', function () {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'ok'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('test-account-key', new Config(), $guzzleClient);

        $result = $client->sendRequest('POST', '/simple');

        expect($result)->toBe(['status' => 'ok']);

        $body = (string) $container[0]['request']->getBody();
        expect($body)->toContain('AccountKey=test-account-key');
    });

    it('throws AuthenticationException on 401 response', function () {
        $mock = new MockHandler([
            new Response(401, [], json_encode(['error' => 'Invalid AccountKey'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('bad-key', new Config(), $guzzleClient);

        $client->sendRequest('POST', '/test');
    })->throws(AuthenticationException::class, 'authentication failed');

    it('throws RateLimitException on 429 response', function () {
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '30'], json_encode(['error' => 'Rate limit exceeded'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        $client->sendRequest('POST', '/test');
    })->throws(RateLimitException::class, 'rate limit exceeded');

    it('includes retry-after seconds on RateLimitException', function () {
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '45'], json_encode(['error' => 'Rate limit'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        try {
            $client->sendRequest('POST', '/test');
        } catch (RateLimitException $e) {
            expect($e->getRetryAfter())->toBe(45);
            expect($e->getStatusCode())->toBe(429);
            expect($e->getResponseBody())->toBe(['error' => 'Rate limit']);
        }
    });

    it('throws NotFoundException on 404 response', function () {
        $mock = new MockHandler([
            new Response(404, [], json_encode(['error' => 'Not found'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        $client->sendRequest('POST', '/test');
    })->throws(NotFoundException::class);

    it('throws ServerException on 500 response', function () {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['error' => 'Internal server error'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        $client->sendRequest('POST', '/test');
    })->throws(ServerException::class);

    it('throws ServerException on 503 response', function () {
        $mock = new MockHandler([
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        try {
            $client->sendRequest('POST', '/test');
        } catch (ServerException $e) {
            expect($e->getStatusCode())->toBe(503);
            expect($e->getResponseBody())->toBe(['error' => 'Service unavailable']);
        }
    });

    it('throws ApiException on other 4xx responses', function () {
        $mock = new MockHandler([
            new Response(422, [], json_encode(['error' => 'Validation failed'])),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        try {
            $client->sendRequest('POST', '/test');
        } catch (ApiException $e) {
            expect($e->getStatusCode())->toBe(422);
            expect($e->getResponseBody())->toBe(['error' => 'Validation failed']);
        }
    });

    it('throws InvalidResponseException on non-JSON response', function () {
        $mock = new MockHandler([
            new Response(200, [], '<html>Not JSON</html>'),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        $client->sendRequest('POST', '/test');
    })->throws(InvalidResponseException::class, 'non-JSON response');

    it('provides raw body on InvalidResponseException', function () {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);

        $guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Client('test-key', new Config(), $guzzleClient);

        try {
            $client->sendRequest('POST', '/test');
        } catch (InvalidResponseException $e) {
            expect($e->getRawBody())->toBe('not json');
            expect($e->getStatusCode())->toBe(200);
        }
    });

    it('throws for HTTP 200 API errors', function (array $errors, array $codes, string $message, string $method) {
        $body = ['error' => $errors, 'request_id' => 'synthetic-request'];
        $mock = new MockHandler([new Response(200, ['Content-Type' => 'application/json'], json_encode($body))]);
        $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        try {
            $client->$method('POST', '/test');
            $this->fail('Expected an API exception');
        } catch (ApiException $e) {
            expect($e::class)->toBe(ApiException::class);
            expect($e->getStatusCode())->toBe(200);
            expect($e->getCode())->toBe(200);
            expect($e->getResponseBody())->toBe($body);
            expect($e->getApiErrorCodes())->toBe($codes);
            expect($e->getApiErrors())->toBe(array_is_list($errors) ? $errors : [$errors]);
            expect($e->getMessage())->toBe($message);
        }
    })->with([
        'object with error_mesg' => [['error_code' => '401', 'error_mesg' => 'Invalid claim_form value.'], ['401'], 'Invalid claim_form value.'],
        'list with error_message' => [[['error_code' => 20, 'error_message' => 'Invalid AccountKey']], ['20'], 'Invalid AccountKey'],
        'object with error_message' => [['error_code' => 711, 'error_message' => 'No claims found.'], ['711'], 'No claims found.'],
        'list with mixed message fields' => [[
            ['error_code' => '710', 'error_mesg' => 'Invalid transmit_date value.'],
            ['error_code' => 401, 'error_message' => 'Invalid claim_form value.'],
        ], ['710', '401'], 'Invalid transmit_date value.; Invalid claim_form value.'],
    ])->with(['sendRequest', 'sendX12Request']);

    it('returns claim status data with an empty top-level error', function () {
        $body = ['error' => [], 'claim' => [['claimid' => '123', 'error' => ['error_code' => '20']]]];
        $mock = new MockHandler([new Response(200, [], json_encode($body))]);
        $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        expect($client->sendRequest('POST', '/services/response/'))->toBe($body);
    });

    it('returns exact X12 bytes and requests JSON error bodies', function () {
        $x12 = "ISA*00*          *00*          ~\r\nST*837*0001~\r\nSE*2*0001~\r\n";
        $container = [];
        $mock = new MockHandler([new Response(200, ['Content-Type' => 'Application/EDI-X12; charset=us-ascii'], $x12)]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => $stack]));

        expect($client->sendX12Request('POST', '/services/claimdata/', ['AccountKey' => 'caller-key']))->toBe($x12);
        expect($container[0]['request']->getHeaderLine('Accept'))->toBe('application/json');
        expect((string) $container[0]['request']->getBody())->toBe('AccountKey=test-key');
    });

    it('rejects unexpected X12 response formats', function (string $contentType, string $body) {
        $mock = new MockHandler([new Response(200, ['Content-Type' => $contentType], $body)]);
        $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        try {
            $client->sendX12Request('POST', '/services/claimdata/');
            $this->fail('Expected an invalid response exception');
        } catch (InvalidResponseException $e) {
            expect($e->getRawBody())->toBe($body);
            expect($e->getStatusCode())->toBe(200);
            expect($e->getMessage())->toContain('non-X12 response');
        }
    })->with([
        ['text/html', '<html>Error</html>'],
        ['application/json', '{"status":"ok"}'],
    ]);

    it('keeps HTTP errors on X12 requests', function (bool $httpErrors) {
        $body = ['error' => ['error_code' => '711', 'error_mesg' => 'No claims found.']];
        $mock = new MockHandler([new Response(429, ['Retry-After' => '30'], json_encode($body))]);
        $client = new Client('test-key', httpClient: new GuzzleClient([
            'handler' => HandlerStack::create($mock),
            'http_errors' => $httpErrors,
        ]));

        try {
            $client->sendX12Request('POST', '/services/claimdata/');
            $this->fail('Expected a rate limit exception');
        } catch (RateLimitException $e) {
            expect($e->getStatusCode())->toBe(429);
            expect($e->getRetryAfter())->toBe(30);
            expect($e->getApiErrorCodes())->toBe(['711']);
            expect($e->getResponseBody())->toBe($body);
        }
    })->with([true, false]);

    it('passes through request failures without a response', function (string $method) {
        $exception = RequestException::create(new Request('POST', '/test'));
        $mock = new MockHandler([$exception]);
        $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        try {
            $client->$method('POST', '/test');
            $this->fail('Expected the request exception');
        } catch (RequestException $e) {
            expect($e)->toBe($exception);
        }
    })->with(['sendRequest', 'sendX12Request']);
});
