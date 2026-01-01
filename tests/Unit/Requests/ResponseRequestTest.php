<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

describe('ResponseRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->responseRequest = new ResponseRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('fetchResponses', function () {
        it('sends request with response ID', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '0'])
                ->andReturn(['responses' => [], 'last_responseid' => '100']);

            $result = $this->responseRequest->fetchResponses('0');

            expect($result)->toBe(['responses' => [], 'last_responseid' => '100']);
        });

        it('sends request with response ID and claim ID', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '50', 'ClaimID' => 'CLAIM123'])
                ->andReturn(['responses' => []]);

            $result = $this->responseRequest->fetchResponses('50', 'CLAIM123');

            expect($result)->toBe(['responses' => []]);
        });

        it('throws exception for empty response ID', function () {
            $this->responseRequest->fetchResponses('');
        })->throws(InvalidArgumentException::class, 'ResponseID cannot be empty');
    });

    describe('fetchAllResponses', function () {
        it('returns a generator', function () {
            $generator = $this->responseRequest->fetchAllResponses();

            expect($generator)->toBeInstanceOf(Generator::class);
        });

        it('yields pages until last_responseid is null', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '0'])
                ->andReturn(['responses' => ['page1'], 'last_responseid' => '100']);

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '100'])
                ->andReturn(['responses' => ['page2'], 'last_responseid' => '200']);

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '200'])
                ->andReturn(['responses' => ['page3'], 'last_responseid' => null]);

            $generator = $this->responseRequest->fetchAllResponses();
            $results = iterator_to_array($generator);

            expect($results)->toHaveCount(3);
            expect($results[0]['responses'])->toBe(['page1']);
            expect($results[1]['responses'])->toBe(['page2']);
            expect($results[2]['responses'])->toBe(['page3']);
        });

        it('passes claim ID to each request', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '0', 'ClaimID' => 'CLAIM123'])
                ->andReturn(['responses' => [], 'last_responseid' => null]);

            $generator = $this->responseRequest->fetchAllResponses('CLAIM123');
            iterator_to_array($generator);
        });

        it('handles single page response', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/response/', ['ResponseID' => '0'])
                ->andReturn(['responses' => ['only_page'], 'last_responseid' => null]);

            $generator = $this->responseRequest->fetchAllResponses();
            $results = iterator_to_array($generator);

            expect($results)->toHaveCount(1);
            expect($results[0]['responses'])->toBe(['only_page']);
        });
    });
});
