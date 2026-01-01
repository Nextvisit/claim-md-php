<?php

use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

describe('EligibilityDTO', function () {
    beforeEach(function () {
        $this->validData = [
            'insLastName' => 'Doe',
            'insFirstName' => 'John',
            'payerId' => 'PAYER123',
            'patientRelationship' => '18',
            'serviceDate' => '20240115',
            'providerNpi' => '1234567890',
            'providerTaxId' => '12-3456789',
        ];
    });

    describe('construction', function () {
        it('creates a DTO with required fields', function () {
            $dto = new EligibilityDTO(...$this->validData);

            expect($dto->insLastName)->toBe('Doe');
            expect($dto->insFirstName)->toBe('John');
            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->patientRelationship)->toBe('18');
            expect($dto->serviceDate)->toBe('20240115');
            expect($dto->providerNpi)->toBe('1234567890');
            expect($dto->providerTaxId)->toBe('12-3456789');
        });

        it('creates a DTO with optional fields', function () {
            $dto = new EligibilityDTO(
                ...$this->validData,
                insMiddleName: 'M',
                insSex: 'M',
                patLastName: 'Smith',
                patFirstName: 'Jane',
                patSex: 'F',
                provTaxIdType: 'E'
            );

            expect($dto->insMiddleName)->toBe('M');
            expect($dto->insSex)->toBe('M');
            expect($dto->patLastName)->toBe('Smith');
            expect($dto->patFirstName)->toBe('Jane');
            expect($dto->patSex)->toBe('F');
            expect($dto->provTaxIdType)->toBe('E');
        });
    });

    describe('validation', function () {
        it('throws exception for missing required field', function () {
            new EligibilityDTO(
                insLastName: '',
                insFirstName: 'John',
                payerId: 'PAYER123',
                patientRelationship: '18',
                serviceDate: '20240115',
                providerNpi: '1234567890',
                providerTaxId: '12-3456789'
            );
        })->throws(InvalidArgumentException::class, 'insLastName is required');

        it('throws exception for invalid service date format', function () {
            new EligibilityDTO(
                insLastName: 'Doe',
                insFirstName: 'John',
                payerId: 'PAYER123',
                patientRelationship: '18',
                serviceDate: '2024-01-15',
                providerNpi: '1234567890',
                providerTaxId: '12-3456789'
            );
        })->throws(InvalidArgumentException::class, 'serviceDate must be in yyyymmdd format');

        it('throws exception for invalid patient relationship', function () {
            new EligibilityDTO(
                insLastName: 'Doe',
                insFirstName: 'John',
                payerId: 'PAYER123',
                patientRelationship: 'XX',
                serviceDate: '20240115',
                providerNpi: '1234567890',
                providerTaxId: '12-3456789'
            );
        })->throws(InvalidArgumentException::class, "patientRelationship must be either '18' or 'G8'");

        it('accepts G8 as patient relationship', function () {
            $dto = new EligibilityDTO(
                ...[...$this->validData, 'patientRelationship' => 'G8']
            );

            expect($dto->patientRelationship)->toBe('G8');
        });

        it('throws exception for invalid insSex', function () {
            new EligibilityDTO(
                ...$this->validData,
                insSex: 'X'
            );
        })->throws(InvalidArgumentException::class, "insSex must be either 'M' or 'F'");

        it('throws exception for invalid patSex', function () {
            new EligibilityDTO(
                ...$this->validData,
                patSex: 'Other'
            );
        })->throws(InvalidArgumentException::class, "patSex must be either 'M' or 'F'");

        it('throws exception for invalid provTaxIdType', function () {
            new EligibilityDTO(
                ...$this->validData,
                provTaxIdType: 'X'
            );
        })->throws(InvalidArgumentException::class, "provTaxIdType must be either 'E' or 'S'");

        it('accepts E as provTaxIdType', function () {
            $dto = new EligibilityDTO(...$this->validData, provTaxIdType: 'E');
            expect($dto->provTaxIdType)->toBe('E');
        });

        it('accepts S as provTaxIdType', function () {
            $dto = new EligibilityDTO(...$this->validData, provTaxIdType: 'S');
            expect($dto->provTaxIdType)->toBe('S');
        });

        it('throws exception for invalid insDob format', function () {
            new EligibilityDTO(
                ...$this->validData,
                insDob: '01-15-1990'
            );
        })->throws(InvalidArgumentException::class, 'insDob must be in yyyymmdd format');

        it('throws exception for invalid patDob format', function () {
            new EligibilityDTO(
                ...$this->validData,
                patDob: '1990/01/15'
            );
        })->throws(InvalidArgumentException::class, 'patDob must be in yyyymmdd format');
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new EligibilityDTO(...$this->validData);

            $array = $dto->toArray();

            expect($array)->toBe([
                'ins_name_l' => 'Doe',
                'ins_name_f' => 'John',
                'payerid' => 'PAYER123',
                'pat_rel' => '18',
                'fdos' => '20240115',
                'prov_npi' => '1234567890',
                'prov_taxid' => '12-3456789',
            ]);
        });

        it('includes optional fields when set', function () {
            $dto = new EligibilityDTO(
                ...$this->validData,
                insMiddleName: 'M',
                insSex: 'M'
            );

            $array = $dto->toArray();

            expect($array)->toHaveKey('ins_name_m');
            expect($array['ins_name_m'])->toBe('M');
            expect($array)->toHaveKey('ins_sex');
            expect($array['ins_sex'])->toBe('M');
        });

        it('filters out null values', function () {
            $dto = new EligibilityDTO(...$this->validData);

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('ins_name_m');
            expect($array)->not->toHaveKey('ins_sex');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $data = [
                'ins_name_l' => 'Doe',
                'ins_name_f' => 'John',
                'payerid' => 'PAYER123',
                'pat_rel' => '18',
                'fdos' => '20240115',
                'prov_npi' => '1234567890',
                'prov_taxid' => '12-3456789',
            ];

            $dto = EligibilityDTO::fromArray($data);

            expect($dto->insLastName)->toBe('Doe');
            expect($dto->insFirstName)->toBe('John');
            expect($dto->payerId)->toBe('PAYER123');
        });

        it('throws exception for missing required field in fromArray', function () {
            $data = [
                'ins_name_l' => 'Doe',
                'ins_name_f' => 'John',
                // Missing payerid
                'pat_rel' => '18',
                'fdos' => '20240115',
                'prov_npi' => '1234567890',
                'prov_taxid' => '12-3456789',
            ];

            EligibilityDTO::fromArray($data);
        })->throws(InvalidArgumentException::class, 'Missing required field: payerid');

        it('includes optional fields from array', function () {
            $data = [
                'ins_name_l' => 'Doe',
                'ins_name_f' => 'John',
                'payerid' => 'PAYER123',
                'pat_rel' => '18',
                'fdos' => '20240115',
                'prov_npi' => '1234567890',
                'prov_taxid' => '12-3456789',
                'ins_sex' => 'M',
                'pat_name_l' => 'Smith',
            ];

            $dto = EligibilityDTO::fromArray($data);

            expect($dto->insSex)->toBe('M');
            expect($dto->patLastName)->toBe('Smith');
        });
    });
});
