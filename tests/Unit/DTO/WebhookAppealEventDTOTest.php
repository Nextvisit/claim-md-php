<?php

use Nextvisit\ClaimMD\DTO\WebhookAppealEventDTO;

describe('WebhookAppealEventDTO', function () {
    describe('construction', function () {
        it('creates a DTO with required fields', function () {
            $dto = new WebhookAppealEventDTO(
                appealId: 'APL-001',
                event: 'created'
            );

            expect($dto->appealId)->toBe('APL-001');
            expect($dto->event)->toBe('created');
        });

        it('creates a DTO with all fields', function () {
            $dto = new WebhookAppealEventDTO(
                appealId: 'APL-001',
                event: 'created',
                eventDetail: 'Appeal created successfully',
                claimId: 'CLM-001',
                remoteClaimId: 'RCLM-001',
                appealType: 'electronic',
                serviceFee: '25.00',
                pages: '5',
                formId: 'FORM-001',
                formName: 'Standard Appeal Form'
            );

            expect($dto->eventDetail)->toBe('Appeal created successfully');
            expect($dto->claimId)->toBe('CLM-001');
            expect($dto->remoteClaimId)->toBe('RCLM-001');
            expect($dto->appealType)->toBe('electronic');
            expect($dto->serviceFee)->toBe('25.00');
            expect($dto->pages)->toBe('5');
            expect($dto->formId)->toBe('FORM-001');
            expect($dto->formName)->toBe('Standard Appeal Form');
        });
    });

    describe('validation', function () {
        it('throws exception when appealId is empty', function () {
            new WebhookAppealEventDTO(appealId: '', event: 'created');
        })->throws(InvalidArgumentException::class, 'appealId is required.');

        it('throws exception when event is empty', function () {
            new WebhookAppealEventDTO(appealId: 'APL-001', event: '');
        })->throws(InvalidArgumentException::class, 'event is required.');

        it('throws exception for invalid event', function () {
            new WebhookAppealEventDTO(appealId: 'APL-001', event: 'invalid');
        })->throws(InvalidArgumentException::class, 'event must be one of: created, mailed, update, faxed, transmitted, failure');

        it('throws exception for invalid appealType', function () {
            new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created', appealType: 'invalid');
        })->throws(InvalidArgumentException::class, 'appealType must be one of: electronic, mail, fax, download');

        it('accepts all valid event values', function () {
            foreach (['created', 'mailed', 'update', 'faxed', 'transmitted', 'failure'] as $event) {
                $dto = new WebhookAppealEventDTO(appealId: 'APL-001', event: $event);
                expect($dto->event)->toBe($event);
            }
        });

        it('accepts all valid appealType values', function () {
            foreach (['electronic', 'mail', 'fax', 'download'] as $type) {
                $dto = new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created', appealType: $type);
                expect($dto->appealType)->toBe($type);
            }
        });

        it('accepts null appealType', function () {
            $dto = new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created');
            expect($dto->appealType)->toBeNull();
        });
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $dto = new WebhookAppealEventDTO(
                appealId: 'APL-001',
                event: 'created',
                claimId: 'CLM-001',
                appealType: 'electronic',
                serviceFee: '25.00'
            );

            expect($dto->toArray())->toBe([
                'appealid'     => 'APL-001',
                'event'        => 'created',
                'claimid'      => 'CLM-001',
                'appeal_type'  => 'electronic',
                'service_fee'  => '25.00',
            ]);
        });

        it('filters out null values', function () {
            $dto = new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created');

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('event_detail');
            expect($array)->not->toHaveKey('claimid');
            expect($array)->not->toHaveKey('appeal_type');
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from array', function () {
            $dto = WebhookAppealEventDTO::fromArray([
                'appealid'       => 'APL-001',
                'event'          => 'mailed',
                'event_detail'   => 'Mailed successfully',
                'claimid'        => 'CLM-001',
                'remote_claimid' => 'RCLM-001',
                'appeal_type'    => 'mail',
                'service_fee'    => '10.00',
                'pages'          => '3',
                'formid'         => 'FORM-001',
                'form_name'      => 'Standard Form',
            ]);

            expect($dto->appealId)->toBe('APL-001');
            expect($dto->event)->toBe('mailed');
            expect($dto->eventDetail)->toBe('Mailed successfully');
            expect($dto->claimId)->toBe('CLM-001');
            expect($dto->remoteClaimId)->toBe('RCLM-001');
            expect($dto->appealType)->toBe('mail');
            expect($dto->serviceFee)->toBe('10.00');
            expect($dto->pages)->toBe('3');
            expect($dto->formId)->toBe('FORM-001');
            expect($dto->formName)->toBe('Standard Form');
        });

        it('throws exception when appealid is missing', function () {
            WebhookAppealEventDTO::fromArray(['event' => 'created']);
        })->throws(InvalidArgumentException::class, 'appealid is required.');

        it('handles missing optional fields', function () {
            $dto = WebhookAppealEventDTO::fromArray([
                'appealid' => 'APL-001',
                'event'    => 'created',
            ]);

            expect($dto->eventDetail)->toBeNull();
            expect($dto->claimId)->toBeNull();
            expect($dto->appealType)->toBeNull();
        });
    });
});
