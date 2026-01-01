<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\Requests\PayerRequest;

describe('PayerRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->payerRequest = new PayerRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('listPayer', function () {
        it('sends request with no filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/payerlist/', [])
                ->andReturn(['payers' => []]);

            $result = $this->payerRequest->listPayer();

            expect($result)->toBe(['payers' => []]);
        });

        it('sends request with payerId filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/payerlist/', ['payerid' => 'PAYER123'])
                ->andReturn(['payers' => [['id' => 'PAYER123', 'name' => 'Test Payer']]]);

            $result = $this->payerRequest->listPayer(payerId: 'PAYER123');

            expect($result)->toBe(['payers' => [['id' => 'PAYER123', 'name' => 'Test Payer']]]);
        });

        it('sends request with payerName filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/payerlist/', ['payer_name' => 'Blue Cross'])
                ->andReturn(['payers' => []]);

            $result = $this->payerRequest->listPayer(payerName: 'Blue Cross');

            expect($result)->toBe(['payers' => []]);
        });

        it('sends request with both filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/payerlist/', ['payerid' => 'BCBS', 'payer_name' => 'Blue Cross'])
                ->andReturn(['payers' => []]);

            $result = $this->payerRequest->listPayer('BCBS', 'Blue Cross');

            expect($result)->toBe(['payers' => []]);
        });
    });
});
