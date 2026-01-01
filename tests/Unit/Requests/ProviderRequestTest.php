<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;
use Nextvisit\ClaimMDWrapper\Requests\ProviderRequest;

describe('ProviderRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->providerRequest = new ProviderRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('enroll', function () {
        it('sends request with ProviderEnrollmentDTO', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                contact: 'John Doe',
                contactEmail: 'john@example.com'
            );

            $expectedData = [
                'payerid' => 'PAYER123',
                'enroll_type' => 'era',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
                'contact' => 'John Doe',
                'contact_email' => 'john@example.com',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/enroll/', $expectedData)
                ->andReturn(['status' => 'enrolled']);

            $result = $this->providerRequest->enroll($dto);

            expect($result)->toBe(['status' => 'enrolled']);
        });

        it('sends request with array data', function () {
            $data = [
                'payerid' => 'PAYER456',
                'enroll_type' => '1500',
                'prov_taxid' => '98-7654321',
                'prov_npi' => '0987654321',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/enroll/', $data)
                ->andReturn(['status' => 'pending']);

            $result = $this->providerRequest->enroll($data);

            expect($result)->toBe(['status' => 'pending']);
        });

        it('sends request with minimal DTO', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'elig',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );

            $expectedData = [
                'payerid' => 'PAYER123',
                'enroll_type' => 'elig',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/enroll/', $expectedData)
                ->andReturn(['status' => 'enrolled']);

            $result = $this->providerRequest->enroll($dto);

            expect($result)->toBe(['status' => 'enrolled']);
        });
    });
});
