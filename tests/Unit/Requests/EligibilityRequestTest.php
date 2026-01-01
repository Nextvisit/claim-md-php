<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;
use Nextvisit\ClaimMDWrapper\Requests\EligibilityRequest;

describe('EligibilityRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->eligibilityRequest = new EligibilityRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('checkEligibilityJSON', function () {
        it('sends request with EligibilityDTO', function () {
            $dto = new EligibilityDTO(
                insLastName: 'Doe',
                insFirstName: 'John',
                payerId: 'PAYER123',
                patientRelationship: '18',
                serviceDate: '20240115',
                providerNpi: '1234567890',
                providerTaxId: '12-3456789'
            );

            $expectedData = [
                'ins_name_l' => 'Doe',
                'ins_name_f' => 'John',
                'payerid' => 'PAYER123',
                'pat_rel' => '18',
                'fdos' => '20240115',
                'prov_npi' => '1234567890',
                'prov_taxid' => '12-3456789',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eligdata/', $expectedData)
                ->andReturn(['eligible' => true]);

            $result = $this->eligibilityRequest->checkEligibilityJSON($dto);

            expect($result)->toBe(['eligible' => true]);
        });

        it('sends request with array data', function () {
            $data = [
                'ins_name_l' => 'Smith',
                'ins_name_f' => 'Jane',
                'payerid' => 'PAYER456',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/eligdata/', $data)
                ->andReturn(['eligible' => false]);

            $result = $this->eligibilityRequest->checkEligibilityJSON($data);

            expect($result)->toBe(['eligible' => false]);
        });
    });

    describe('checkEligibility270271', function () {
        it('throws exception when file is not a resource', function () {
            $this->eligibilityRequest->checkEligibility270271('not a resource');
        })->throws(InvalidArgumentException::class, 'File must be a valid resource');

        it('throws exception for array input', function () {
            $this->eligibilityRequest->checkEligibility270271(['data']);
        })->throws(InvalidArgumentException::class, 'File must be a valid resource');

        it('throws exception for null input', function () {
            $this->eligibilityRequest->checkEligibility270271(null);
        })->throws(InvalidArgumentException::class, 'File must be a valid resource');
    });
});
