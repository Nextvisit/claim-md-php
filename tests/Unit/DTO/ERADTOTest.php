<?php

use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

describe('ERADTO', function () {
    describe('construction', function () {
        it('creates an empty DTO with no parameters', function () {
            $dto = new ERADTO();

            expect($dto->checkDate)->toBeNull();
            expect($dto->receivedDate)->toBeNull();
            expect($dto->checkNumber)->toBeNull();
        });

        it('creates a DTO with all optional fields', function () {
            $dto = new ERADTO(
                checkDate: '01-15-2024',
                receivedDate: '01-16-2024',
                receivedAfterDate: '01-10-2024',
                checkNumber: 'CHK123',
                checkAmount: '1500.00',
                payerId: 'PAYER123',
                npi: '1234567890',
                taxId: '12-3456789',
                newOnly: '1',
                eraId: 'ERA123',
                page: '1'
            );

            expect($dto->checkDate)->toBe('01-15-2024');
            expect($dto->receivedDate)->toBe('01-16-2024');
            expect($dto->receivedAfterDate)->toBe('01-10-2024');
            expect($dto->checkNumber)->toBe('CHK123');
            expect($dto->checkAmount)->toBe('1500.00');
            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->npi)->toBe('1234567890');
            expect($dto->taxId)->toBe('12-3456789');
            expect($dto->newOnly)->toBe('1');
            expect($dto->eraId)->toBe('ERA123');
            expect($dto->page)->toBe('1');
        });
    });

    describe('validation', function () {
        it('throws exception for invalid checkDate format', function () {
            new ERADTO(checkDate: '2024-01-15');
        })->throws(InvalidArgumentException::class, "checkDate must be in mm-dd-yyyy format or 'today'/'yesterday'");

        it('throws exception for invalid receivedDate format', function () {
            new ERADTO(receivedDate: '15-01-2024');
        })->throws(InvalidArgumentException::class, "receivedDate must be in mm-dd-yyyy format or 'today'/'yesterday'");

        it('throws exception for invalid receivedAfterDate format', function () {
            new ERADTO(receivedAfterDate: 'invalid-date');
        })->throws(InvalidArgumentException::class, "receivedAfterDate must be in mm-dd-yyyy format or 'today'/'yesterday'");

        it('accepts today for receivedDate', function () {
            $dto = new ERADTO(receivedDate: 'today');

            expect($dto->receivedDate)->toBe('today');
        });

        it('accepts yesterday for receivedDate', function () {
            $dto = new ERADTO(receivedDate: 'yesterday');

            expect($dto->receivedDate)->toBe('yesterday');
        });

        it('accepts Today in mixed case for receivedDate', function () {
            $dto = new ERADTO(receivedDate: 'Today');

            expect($dto->receivedDate)->toBe('Today');
        });

        it('accepts yesterday for receivedAfterDate', function () {
            $dto = new ERADTO(receivedAfterDate: 'yesterday');

            expect($dto->receivedAfterDate)->toBe('yesterday');
        });

        it('throws exception for invalid newOnly value', function () {
            new ERADTO(newOnly: '2');
        })->throws(InvalidArgumentException::class, "newOnly must be either '1' (true) or '0' (false)");

        it('throws exception for non-string newOnly value', function () {
            new ERADTO(newOnly: 'yes');
        })->throws(InvalidArgumentException::class, "newOnly must be either '1' (true) or '0' (false)");

        it('accepts 0 for newOnly', function () {
            $dto = new ERADTO(newOnly: '0');

            expect($dto->newOnly)->toBe('0');
        });

        it('accepts 1 for newOnly', function () {
            $dto = new ERADTO(newOnly: '1');

            expect($dto->newOnly)->toBe('1');
        });

        it('accepts valid mm-dd-yyyy date format', function () {
            $dto = new ERADTO(checkDate: '12-31-2024');

            expect($dto->checkDate)->toBe('12-31-2024');
        });
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new ERADTO(
                checkDate: '01-15-2024',
                checkNumber: 'CHK123',
                payerId: 'PAYER123'
            );

            $array = $dto->toArray();

            expect($array)->toBe([
                'CheckDate' => '01-15-2024',
                'CheckNumber' => 'CHK123',
                'PayerID' => 'PAYER123',
            ]);
        });

        it('returns empty array when no fields are set', function () {
            $dto = new ERADTO();

            $array = $dto->toArray();

            expect($array)->toBe([]);
        });

        it('filters out null values', function () {
            $dto = new ERADTO(eraId: 'ERA123');

            $array = $dto->toArray();

            expect($array)->toBe(['ERAID' => 'ERA123']);
            expect($array)->not->toHaveKey('CheckDate');
            expect($array)->not->toHaveKey('CheckNumber');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $data = [
                'CheckDate' => '01-15-2024',
                'ReceivedDate' => 'today',
                'CheckNumber' => 'CHK123',
                'CheckAmount' => '1500.00',
                'PayerID' => 'PAYER123',
                'NPI' => '1234567890',
                'TaxID' => '12-3456789',
                'NewOnly' => '1',
                'ERAID' => 'ERA123',
                'Page' => '1',
            ];

            $dto = ERADTO::fromArray($data);

            expect($dto->checkDate)->toBe('01-15-2024');
            expect($dto->receivedDate)->toBe('today');
            expect($dto->checkNumber)->toBe('CHK123');
            expect($dto->checkAmount)->toBe('1500.00');
            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->npi)->toBe('1234567890');
            expect($dto->taxId)->toBe('12-3456789');
            expect($dto->newOnly)->toBe('1');
            expect($dto->eraId)->toBe('ERA123');
            expect($dto->page)->toBe('1');
        });

        it('handles empty array', function () {
            $dto = ERADTO::fromArray([]);

            expect($dto->checkDate)->toBeNull();
            expect($dto->eraId)->toBeNull();
        });

        it('handles partial data', function () {
            $data = [
                'PayerID' => 'PAYER123',
                'ERAID' => 'ERA456',
            ];

            $dto = ERADTO::fromArray($data);

            expect($dto->payerId)->toBe('PAYER123');
            expect($dto->eraId)->toBe('ERA456');
            expect($dto->checkDate)->toBeNull();
        });
    });
});
