<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;
use Nextvisit\ClaimMDWrapper\Requests\ERARequest;

describe('ERARequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->eraRequest = new ERARequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('getList', function () {
        it('sends request with empty data when no params provided', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eralist/')
                ->andReturn(['data' => []]);

            $result = $this->eraRequest->getList();

            expect($result)->toBe(['data' => []]);
        });

        it('sends request with ERADTO data', function () {
            $dto = new ERADTO(payerId: 'PAYER123', newOnly: '1');

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eralist/', ['PayerID' => 'PAYER123', 'NewOnly' => '1'])
                ->andReturn(['data' => ['era1', 'era2']]);

            $result = $this->eraRequest->getList($dto);

            expect($result)->toBe(['data' => ['era1', 'era2']]);
        });

        it('sends request with array data', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eralist/', ['PayerID' => 'TEST123'])
                ->andReturn(['data' => []]);

            $result = $this->eraRequest->getList(['PayerID' => 'TEST123']);

            expect($result)->toBe(['data' => []]);
        });

        it('handles null parameter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eralist/')
                ->andReturn(['status' => 'ok']);

            $result = $this->eraRequest->getList(null);

            expect($result)->toBe(['status' => 'ok']);
        });
    });

    describe('getJson', function () {
        it('sends request with era ID', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eradata/', ['eraid' => 'ERA123'])
                ->andReturn(['json_data' => 'test']);

            $result = $this->eraRequest->getJson('ERA123');

            expect($result)->toBe(['json_data' => 'test']);
        });
    });

    describe('getPDF', function () {
        it('sends request with era ID only', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/erapdf/', ['eraid' => 'ERA123'])
                ->andReturn(['pdf' => 'base64data']);

            $result = $this->eraRequest->getPDF('ERA123');

            expect($result)->toBe(['pdf' => 'base64data']);
        });

        it('sends request with era ID and PCN', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/erapdf/', ['eraid' => 'ERA123', 'pcn' => 'PCN456'])
                ->andReturn(['pdf' => 'base64data']);

            $result = $this->eraRequest->getPDF('ERA123', 'PCN456');

            expect($result)->toBe(['pdf' => 'base64data']);
        });
    });

    describe('get835', function () {
        it('sends request with era ID', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/era835/', ['eraid' => 'ERA123'])
                ->andReturn(['835_data' => 'test']);

            $result = $this->eraRequest->get835('ERA123');

            expect($result)->toBe(['835_data' => 'test']);
        });
    });
});
