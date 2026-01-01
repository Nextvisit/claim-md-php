<?php

use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

describe('ProviderEnrollmentDTO', function () {
    describe('construction', function () {
        it('creates a DTO with required fields and NPI', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );

            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->enrollType)->toBe('era');
            expect($dto->provTaxId)->toBe('12-3456789');
            expect($dto->provNpi)->toBe('1234567890');
        });

        it('creates a DTO with name fields when no NPI', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: '1500',
                provTaxId: '12-3456789',
                provNameLast: 'Smith',
                provNameFirst: 'John'
            );

            expect($dto->provNameLast)->toBe('Smith');
            expect($dto->provNameFirst)->toBe('John');
        });

        it('creates a DTO with all optional fields', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                provNameLast: 'Smith',
                provNameFirst: 'John',
                provNameMiddle: 'M',
                contact: 'Jane Doe',
                contactTitle: 'Manager',
                contactEmail: 'jane@example.com',
                contactPhone: '555-123-4567',
                contactFax: '555-987-6543',
                provId: 'PROV123',
                provAddr1: '123 Main St',
                provAddr2: 'Suite 100',
                provCity: 'Anytown',
                provState: 'CA',
                provZip: '12345'
            );

            expect($dto->contact)->toBe('Jane Doe');
            expect($dto->contactTitle)->toBe('Manager');
            expect($dto->contactEmail)->toBe('jane@example.com');
            expect($dto->provAddr1)->toBe('123 Main St');
            expect($dto->provState)->toBe('CA');
        });
    });

    describe('validation', function () {
        it('throws exception for empty payerId', function () {
            new ProviderEnrollmentDTO(
                payerId: '',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );
        })->throws(InvalidArgumentException::class, 'payerId is required.');

        it('throws exception for empty enrollType', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: '',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );
        })->throws(InvalidArgumentException::class, 'enrollType is required.');

        it('throws exception for empty provTaxId', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '',
                provNpi: '1234567890'
            );
        })->throws(InvalidArgumentException::class, 'provTaxId is required.');

        it('throws exception for invalid enrollType', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'invalid',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );
        })->throws(InvalidArgumentException::class, 'enrollType must be one of: era, 1500, ub, elig, attach');

        it('accepts all valid enrollType values', function () {
            $validTypes = ['era', '1500', 'ub', 'elig', 'attach'];

            foreach ($validTypes as $type) {
                $dto = new ProviderEnrollmentDTO(
                    payerId: 'PAYER123',
                    enrollType: $type,
                    provTaxId: '12-3456789',
                    provNpi: '1234567890'
                );

                expect($dto->enrollType)->toBe($type);
            }
        });

        it('throws exception when NPI not provided and provNameLast is missing', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789'
            );
        })->throws(InvalidArgumentException::class, 'provNameLast is required when provNpi is not provided.');

        it('throws exception when NPI not provided and provNameFirst is missing for individual', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNameLast: 'Smith'
            );
        })->throws(InvalidArgumentException::class, 'provNameFirst is required when provNpi is not provided and the provider is an individual.');

        it('throws exception for invalid email', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                contactEmail: 'invalid-email'
            );
        })->throws(InvalidArgumentException::class, 'contactEmail must be a valid email address.');

        it('throws exception for invalid state code', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                provState: 'California'
            );
        })->throws(InvalidArgumentException::class, 'provState must be a valid two-letter state code.');

        it('throws exception for lowercase state code', function () {
            new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                provState: 'ca'
            );
        })->throws(InvalidArgumentException::class, 'provState must be a valid two-letter state code.');
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: 'era',
                provTaxId: '12-3456789',
                provNpi: '1234567890',
                contact: 'Jane Doe',
                contactEmail: 'jane@example.com'
            );

            $array = $dto->toArray();

            expect($array)->toBe([
                'payerid' => 'PAYER123',
                'enroll_type' => 'era',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
                'contact' => 'Jane Doe',
                'contact_email' => 'jane@example.com',
            ]);
        });

        it('filters out null values', function () {
            $dto = new ProviderEnrollmentDTO(
                payerId: 'PAYER123',
                enrollType: '1500',
                provTaxId: '12-3456789',
                provNpi: '1234567890'
            );

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('prov_name_l');
            expect($array)->not->toHaveKey('contact_email');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $data = [
                'payerid' => 'PAYER123',
                'enroll_type' => 'era',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
                'contact' => 'Jane Doe',
                'contact_email' => 'jane@example.com',
                'prov_state' => 'NY',
            ];

            $dto = ProviderEnrollmentDTO::fromArray($data);

            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->enrollType)->toBe('era');
            expect($dto->provTaxId)->toBe('12-3456789');
            expect($dto->provNpi)->toBe('1234567890');
            expect($dto->contact)->toBe('Jane Doe');
            expect($dto->contactEmail)->toBe('jane@example.com');
            expect($dto->provState)->toBe('NY');
        });

        it('throws exception for missing required field payerid', function () {
            ProviderEnrollmentDTO::fromArray([
                'enroll_type' => 'era',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
            ]);
        })->throws(InvalidArgumentException::class, 'payerid is required.');

        it('throws exception for missing required field enroll_type', function () {
            ProviderEnrollmentDTO::fromArray([
                'payerid' => 'PAYER123',
                'prov_taxid' => '12-3456789',
                'prov_npi' => '1234567890',
            ]);
        })->throws(InvalidArgumentException::class, 'enroll_type is required.');

        it('throws exception for missing required field prov_taxid', function () {
            ProviderEnrollmentDTO::fromArray([
                'payerid' => 'PAYER123',
                'enroll_type' => 'era',
                'prov_npi' => '1234567890',
            ]);
        })->throws(InvalidArgumentException::class, 'prov_taxid is required.');
    });
});
