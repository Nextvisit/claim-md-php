<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use Nextvisit\ClaimMD\Client;
use Nextvisit\ClaimMD\Config;
use Nextvisit\ClaimMD\Exceptions\ApiException;
use Nextvisit\ClaimMD\Exceptions\AuthenticationException;
use Nextvisit\ClaimMD\Exceptions\InvalidResponseException;
use Nextvisit\ClaimMD\Exceptions\NotFoundException;
use Nextvisit\ClaimMD\Exceptions\RateLimitException;
use Nextvisit\ClaimMD\Exceptions\ServerException;

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

    it('sends a multipart request when isMultipart is true', function () {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['uploaded' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('test-account-key', new Config(), $guzzleClient);

        $result = $client->sendRequest('POST', '/upload', ['file' => 'content'], true);

        expect($result)->toBe(['uploaded' => true]);

        $request = $container[0]['request'];
        $contentType = $request->getHeaderLine('Content-Type');
        expect($contentType)->toContain('multipart/form-data');
    });

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
});
