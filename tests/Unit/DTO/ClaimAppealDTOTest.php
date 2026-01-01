<?php

use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;

describe('ClaimAppealDTO', function () {
    describe('construction', function () {
        it('creates a DTO with claimId', function () {
            $dto = new ClaimAppealDTO(claimId: '12345');

            expect($dto->claimId)->toBe('12345');
        });

        it('creates a DTO with remoteClaimId', function () {
            $dto = new ClaimAppealDTO(remoteClaimId: 'REMOTE-123');

            expect($dto->remoteClaimId)->toBe('REMOTE-123');
        });

        it('creates a DTO with all optional fields', function () {
            $dto = new ClaimAppealDTO(
                claimId: '12345',
                contactName: 'John Doe',
                contactTitle: 'Manager',
                contactEmail: 'john@example.com',
                contactPhone: '555-123-4567',
                contactFax: '555-987-6543',
                contactAddr1: '123 Main St',
                contactAddr2: 'Suite 100',
                contactCity: 'Anytown',
                contactState: 'CA',
                contactZip: '12345'
            );

            expect($dto->contactName)->toBe('John Doe');
            expect($dto->contactTitle)->toBe('Manager');
            expect($dto->contactEmail)->toBe('john@example.com');
            expect($dto->contactPhone)->toBe('555-123-4567');
            expect($dto->contactFax)->toBe('555-987-6543');
            expect($dto->contactAddr1)->toBe('123 Main St');
            expect($dto->contactAddr2)->toBe('Suite 100');
            expect($dto->contactCity)->toBe('Anytown');
            expect($dto->contactState)->toBe('CA');
            expect($dto->contactZip)->toBe('12345');
        });
    });

    describe('validation', function () {
        it('throws exception when neither claimId nor remoteClaimId is provided', function () {
            new ClaimAppealDTO();
        })->throws(InvalidArgumentException::class, 'Either claimId or remoteClaimId must be provided.');

        it('throws exception for invalid email', function () {
            new ClaimAppealDTO(claimId: '12345', contactEmail: 'invalid-email');
        })->throws(InvalidArgumentException::class, 'contactEmail must be a valid email address.');

        it('throws exception for invalid phone number', function () {
            new ClaimAppealDTO(claimId: '12345', contactPhone: 'abc-invalid');
        })->throws(InvalidArgumentException::class, 'contactPhone must be a valid phone number.');

        it('throws exception for invalid fax number', function () {
            new ClaimAppealDTO(claimId: '12345', contactFax: 'invalid!fax');
        })->throws(InvalidArgumentException::class, 'contactFax must be a valid phone number.');

        it('throws exception for invalid state code', function () {
            new ClaimAppealDTO(claimId: '12345', contactState: 'California');
        })->throws(InvalidArgumentException::class, 'contactState must be a valid two-letter state code.');

        it('throws exception for lowercase state code', function () {
            new ClaimAppealDTO(claimId: '12345', contactState: 'ca');
        })->throws(InvalidArgumentException::class, 'contactState must be a valid two-letter state code.');

        it('accepts valid phone formats', function () {
            $dto = new ClaimAppealDTO(
                claimId: '12345',
                contactPhone: '+1 (555) 123-4567'
            );

            expect($dto->contactPhone)->toBe('+1 (555) 123-4567');
        });
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new ClaimAppealDTO(
                claimId: '12345',
                remoteClaimId: 'REMOTE-123',
                contactName: 'John Doe',
                contactEmail: 'john@example.com'
            );

            $array = $dto->toArray();

            expect($array)->toBe([
                'claimid' => '12345',
                'remote_claimid' => 'REMOTE-123',
                'contact_name' => 'John Doe',
                'contact_email' => 'john@example.com',
            ]);
        });

        it('filters out null values', function () {
            $dto = new ClaimAppealDTO(claimId: '12345');

            $array = $dto->toArray();

            expect($array)->toBe(['claimid' => '12345']);
            expect($array)->not->toHaveKey('remote_claimid');
            expect($array)->not->toHaveKey('contact_name');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $data = [
                'claimid' => '12345',
                'remote_claimid' => 'REMOTE-123',
                'contact_name' => 'John Doe',
                'contact_email' => 'john@example.com',
                'contact_phone' => '555-123-4567',
                'contact_state' => 'NY',
            ];

            $dto = ClaimAppealDTO::fromArray($data);

            expect($dto->claimId)->toBe('12345');
            expect($dto->remoteClaimId)->toBe('REMOTE-123');
            expect($dto->contactName)->toBe('John Doe');
            expect($dto->contactEmail)->toBe('john@example.com');
            expect($dto->contactPhone)->toBe('555-123-4567');
            expect($dto->contactState)->toBe('NY');
        });

        it('handles missing optional fields', function () {
            $data = ['claimid' => '12345'];

            $dto = ClaimAppealDTO::fromArray($data);

            expect($dto->claimId)->toBe('12345');
            expect($dto->contactName)->toBeNull();
            expect($dto->contactEmail)->toBeNull();
        });
    });
});
