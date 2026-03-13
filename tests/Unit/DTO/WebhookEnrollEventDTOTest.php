<?php

use Nextvisit\ClaimMD\DTO\WebhookEnrollEventDTO;

describe('WebhookEnrollEventDTO', function () {
    describe('construction', function () {
        it('creates a DTO with required fields', function () {
            $dto = new WebhookEnrollEventDTO(
                enrollId: 'ENR-001',
                event: 'enrolled',
                enrollType: 'era'
            );

            expect($dto->enrollId)->toBe('ENR-001');
            expect($dto->event)->toBe('enrolled');
            expect($dto->enrollType)->toBe('era');
        });

        it('creates a DTO with all fields', function () {
            $dto = new WebhookEnrollEventDTO(
                enrollId: 'ENR-001',
                event: 'enrolled',
                enrollType: 'era',
                eventDetail: 'Enrollment completed successfully',
                provNpi: '1234567890',
                provTaxId: '123456789',
                provId: 'PROV-001',
                payerId: 'PAYER-001'
            );

            expect($dto->eventDetail)->toBe('Enrollment completed successfully');
            expect($dto->provNpi)->toBe('1234567890');
            expect($dto->provTaxId)->toBe('123456789');
            expect($dto->provId)->toBe('PROV-001');
            expect($dto->payerId)->toBe('PAYER-001');
        });
    });

    describe('validation', function () {
        it('throws exception when enrollId is empty', function () {
            new WebhookEnrollEventDTO(enrollId: '', event: 'enrolled', enrollType: 'era');
        })->throws(InvalidArgumentException::class, 'enrollId is required.');

        it('throws exception when event is empty', function () {
            new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: '', enrollType: 'era');
        })->throws(InvalidArgumentException::class, 'event is required.');

        it('throws exception when enrollType is empty', function () {
            new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: '');
        })->throws(InvalidArgumentException::class, 'enrollType is required.');

        it('throws exception for invalid event', function () {
            new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'invalid', enrollType: 'era');
        })->throws(InvalidArgumentException::class, 'event must be one of: enrolled, received, completed, rejected');

        it('throws exception for invalid enrollType', function () {
            new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: 'invalid');
        })->throws(InvalidArgumentException::class, 'enrollType must be one of: era, 1500, ub, elig, attach');

        it('accepts all valid event values', function () {
            foreach (['enrolled', 'received', 'completed', 'rejected'] as $event) {
                $dto = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: $event, enrollType: 'era');
                expect($dto->event)->toBe($event);
            }
        });

        it('accepts all valid enrollType values', function () {
            foreach (['era', '1500', 'ub', 'elig', 'attach'] as $type) {
                $dto = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: $type);
                expect($dto->enrollType)->toBe($type);
            }
        });
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new WebhookEnrollEventDTO(
                enrollId: 'ENR-001',
                event: 'enrolled',
                enrollType: 'era',
                provNpi: '1234567890',
                payerId: 'PAYER-001'
            );

            expect($dto->toArray())->toBe([
                'enrollid'    => 'ENR-001',
                'event'       => 'enrolled',
                'enroll_type' => 'era',
                'prov_npi'    => '1234567890',
                'payerid'     => 'PAYER-001',
            ]);
        });

        it('filters out null values', function () {
            $dto = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: 'era');

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('event_detail');
            expect($array)->not->toHaveKey('prov_npi');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $dto = WebhookEnrollEventDTO::fromArray([
                'enrollid'     => 'ENR-001',
                'event'        => 'enrolled',
                'enroll_type'  => 'era',
                'event_detail' => 'Details here',
                'prov_npi'     => '1234567890',
                'prov_taxid'   => '123456789',
                'prov_id'      => 'PROV-001',
                'payerid'      => 'PAYER-001',
            ]);

            expect($dto->enrollId)->toBe('ENR-001');
            expect($dto->event)->toBe('enrolled');
            expect($dto->enrollType)->toBe('era');
            expect($dto->eventDetail)->toBe('Details here');
            expect($dto->provNpi)->toBe('1234567890');
            expect($dto->provTaxId)->toBe('123456789');
            expect($dto->provId)->toBe('PROV-001');
            expect($dto->payerId)->toBe('PAYER-001');
        });

        it('throws exception when enrollid is missing', function () {
            WebhookEnrollEventDTO::fromArray(['event' => 'enrolled', 'enroll_type' => 'era']);
        })->throws(InvalidArgumentException::class, 'enrollid is required.');

        it('handles missing optional fields', function () {
            $dto = WebhookEnrollEventDTO::fromArray([
                'enrollid'    => 'ENR-001',
                'event'       => 'enrolled',
                'enroll_type' => 'era',
            ]);

            expect($dto->eventDetail)->toBeNull();
            expect($dto->provNpi)->toBeNull();
        });
    });
});
