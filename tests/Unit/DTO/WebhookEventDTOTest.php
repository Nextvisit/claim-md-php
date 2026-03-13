<?php

use Nextvisit\ClaimMD\DTO\WebhookEventDTO;
use Nextvisit\ClaimMD\DTO\WebhookEnrollEventDTO;
use Nextvisit\ClaimMD\DTO\WebhookAppealEventDTO;

describe('WebhookEventDTO', function () {
    describe('construction', function () {
        it('creates an enroll event DTO', function () {
            $enroll = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: 'era');
            $dto = new WebhookEventDTO(
                eventId: 'EVT-001',
                eventType: 'enroll',
                eventTime: '2026-03-13T12:00:00Z',
                enroll: $enroll
            );

            expect($dto->eventId)->toBe('EVT-001');
            expect($dto->eventType)->toBe('enroll');
            expect($dto->eventTime)->toBe('2026-03-13T12:00:00Z');
            expect($dto->enroll)->toBe($enroll);
            expect($dto->appeal)->toBeNull();
        });

        it('creates an appeal event DTO', function () {
            $appeal = new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created');
            $dto = new WebhookEventDTO(
                eventId: 'EVT-002',
                eventType: 'appeal',
                appeal: $appeal
            );

            expect($dto->eventType)->toBe('appeal');
            expect($dto->appeal)->toBe($appeal);
            expect($dto->enroll)->toBeNull();
        });
    });

    describe('validation', function () {
        it('throws exception when eventId is empty', function () {
            new WebhookEventDTO(eventId: '', eventType: 'enroll');
        })->throws(InvalidArgumentException::class, 'eventId is required.');

        it('throws exception when eventType is empty', function () {
            new WebhookEventDTO(eventId: 'EVT-001', eventType: '');
        })->throws(InvalidArgumentException::class, 'eventType is required.');

        it('throws exception for invalid eventType', function () {
            new WebhookEventDTO(eventId: 'EVT-001', eventType: 'invalid');
        })->throws(InvalidArgumentException::class, 'eventType must be one of: enroll, appeal');

        it('throws exception when enroll data is missing for enroll event', function () {
            new WebhookEventDTO(eventId: 'EVT-001', eventType: 'enroll');
        })->throws(InvalidArgumentException::class, 'enroll event data is required when eventType is "enroll".');

        it('throws exception when appeal data is missing for appeal event', function () {
            new WebhookEventDTO(eventId: 'EVT-001', eventType: 'appeal');
        })->throws(InvalidArgumentException::class, 'appeal event data is required when eventType is "appeal".');
    });

    describe('toArray', function () {
        it('converts enroll event to array', function () {
            $enroll = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: 'era');
            $dto = new WebhookEventDTO(
                eventId: 'EVT-001',
                eventType: 'enroll',
                eventTime: '2026-03-13T12:00:00Z',
                enroll: $enroll
            );

            expect($dto->toArray())->toBe([
                'eventid'    => 'EVT-001',
                'event_type' => 'enroll',
                'event_time' => '2026-03-13T12:00:00Z',
                'event_data' => [
                    'enroll' => [
                        'enrollid'    => 'ENR-001',
                        'event'       => 'enrolled',
                        'enroll_type' => 'era',
                    ],
                ],
            ]);
        });

        it('converts appeal event to array', function () {
            $appeal = new WebhookAppealEventDTO(appealId: 'APL-001', event: 'created', appealType: 'electronic');
            $dto = new WebhookEventDTO(
                eventId: 'EVT-002',
                eventType: 'appeal',
                appeal: $appeal
            );

            expect($dto->toArray())->toBe([
                'eventid'    => 'EVT-002',
                'event_type' => 'appeal',
                'event_data' => [
                    'appeal' => [
                        'appealid'    => 'APL-001',
                        'event'       => 'created',
                        'appeal_type' => 'electronic',
                    ],
                ],
            ]);
        });
    });

    describe('fromArray', function () {
        it('creates an enroll event DTO from array', function () {
            $dto = WebhookEventDTO::fromArray([
                'eventid'    => 'EVT-001',
                'event_type' => 'enroll',
                'event_time' => '2026-03-13T12:00:00Z',
                'event_data' => [
                    'enroll' => [
                        'enrollid'    => 'ENR-001',
                        'event'       => 'enrolled',
                        'enroll_type' => 'era',
                    ],
                ],
            ]);

            expect($dto->eventId)->toBe('EVT-001');
            expect($dto->eventType)->toBe('enroll');
            expect($dto->enroll)->toBeInstanceOf(WebhookEnrollEventDTO::class);
            expect($dto->enroll->enrollId)->toBe('ENR-001');
        });

        it('creates an appeal event DTO from array', function () {
            $dto = WebhookEventDTO::fromArray([
                'eventid'    => 'EVT-002',
                'event_type' => 'appeal',
                'event_data' => [
                    'appeal' => [
                        'appealid' => 'APL-001',
                        'event'    => 'created',
                    ],
                ],
            ]);

            expect($dto->eventType)->toBe('appeal');
            expect($dto->appeal)->toBeInstanceOf(WebhookAppealEventDTO::class);
            expect($dto->appeal->appealId)->toBe('APL-001');
        });

        it('throws exception when eventid is missing', function () {
            WebhookEventDTO::fromArray(['event_type' => 'enroll']);
        })->throws(InvalidArgumentException::class, 'eventid is required.');
    });
});
