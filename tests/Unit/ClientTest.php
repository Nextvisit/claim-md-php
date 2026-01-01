<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\Config;

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
});
