<?php

use Nextvisit\ClaimMD\DTO\WebhookPayloadDTO;
use Nextvisit\ClaimMD\DTO\WebhookEventDTO;
use Nextvisit\ClaimMD\DTO\WebhookEnrollEventDTO;
use Nextvisit\ClaimMD\DTO\WebhookAppealEventDTO;

describe('WebhookPayloadDTO', function () {
    describe('construction', function () {
        it('creates a DTO with required fields', function () {
            $dto = new WebhookPayloadDTO(
                utcTime: '2026-03-13T12:00:00Z',
                acctNumber: 'ACCT-001'
            );

            expect($dto->utcTime)->toBe('2026-03-13T12:00:00Z');
            expect($dto->acctNumber)->toBe('ACCT-001');
            expect($dto->remoteAcctNumber)->toBeNull();
            expect($dto->events)->toBe([]);
        });

        it('creates a DTO with all fields', function () {
            $enroll = new WebhookEnrollEventDTO(enrollId: 'ENR-001', event: 'enrolled', enrollType: 'era');
            $event = new WebhookEventDTO(eventId: 'EVT-001', eventType: 'enroll', enroll: $enroll);

            $dto = new WebhookPayloadDTO(
                utcTime: '2026-03-13T12:00:00Z',
                acctNumber: 'ACCT-001',
                remoteAcctNumber: 'REMOTE-001',
                events: [$event]
            );

            expect($dto->remoteAcctNumber)->toBe('REMOTE-001');
            expect($dto->events)->toHaveCount(1);
            expect($dto->events[0])->toBe($event);
        });
    });

    describe('validation', function () {
        it('throws exception when utcTime is empty', function () {
            new WebhookPayloadDTO(utcTime: '', acctNumber: 'ACCT-001');
        })->throws(InvalidArgumentException::class, 'utcTime is required.');

        it('throws exception when acctNumber is empty', function () {
            new WebhookPayloadDTO(utcTime: '2026-03-13T12:00:00Z', acctNumber: '');
        })->throws(InvalidArgumentException::class, 'acctNumber is required.');

        it('throws exception when events array contains non-WebhookEventDTO', function () {
            new WebhookPayloadDTO(
                utcTime: '2026-03-13T12:00:00Z',
                acctNumber: 'ACCT-001',
                events: ['not-a-dto']
            );
        })->throws(InvalidArgumentException::class, 'Event at index 0 must be an instance of WebhookEventDTO.');
    });

    describe('toArray', function () {
        it('converts to array with correct keys', function () {
            $enroll = new WebhookEnrollEventDTO(
                enrollId: 'ENR-001',
                event: 'enrolled',
                enrollType: 'era',
                provNpi: '1234567890',
                payerId: 'PAYER-001'
            );
            $event = new WebhookEventDTO(
                eventId: 'EVT-001',
                eventType: 'enroll',
                eventTime: '2026-03-13T12:00:00Z',
                enroll: $enroll
            );

            $dto = new WebhookPayloadDTO(
                utcTime: '2026-03-13T12:00:00Z',
                acctNumber: 'ACCT-001',
                remoteAcctNumber: 'REMOTE-001',
                events: [$event]
            );

            expect($dto->toArray())->toBe([
                'UTCTime'            => '2026-03-13T12:00:00Z',
                'acct_number'        => 'ACCT-001',
                'remote_acct_number' => 'REMOTE-001',
                'events'             => [
                    [
                        'eventid'    => 'EVT-001',
                        'event_type' => 'enroll',
                        'event_time' => '2026-03-13T12:00:00Z',
                        'event_data' => [
                            'enroll' => [
                                'enrollid'    => 'ENR-001',
                                'event'       => 'enrolled',
                                'enroll_type' => 'era',
                                'prov_npi'    => '1234567890',
                                'payerid'     => 'PAYER-001',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        it('filters out null remoteAcctNumber', function () {
            $dto = new WebhookPayloadDTO(
                utcTime: '2026-03-13T12:00:00Z',
                acctNumber: 'ACCT-001'
            );

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('remote_acct_number');
            expect($array['events'])->toBe([]);
        });
    });

    describe('fromArray', function () {
        it('creates a DTO from a full webhook payload', function () {
            $data = [
                'UTCTime'            => '2026-03-13T12:00:00Z',
                'acct_number'        => 'ACCT-001',
                'remote_acct_number' => 'REMOTE-001',
                'events'             => [
                    [
                        'eventid'    => 'EVT-001',
                        'event_type' => 'enroll',
                        'event_time' => '2026-03-13T11:00:00Z',
                        'event_data' => [
                            'enroll' => [
                                'enrollid'    => 'ENR-001',
                                'event'       => 'enrolled',
                                'enroll_type' => 'era',
                                'prov_npi'    => '1234567890',
                                'payerid'     => 'PAYER-001',
                            ],
                        ],
                    ],
                    [
                        'eventid'    => 'EVT-002',
                        'event_type' => 'appeal',
                        'event_time' => '2026-03-13T11:30:00Z',
                        'event_data' => [
                            'appeal' => [
                                'appealid'    => 'APL-001',
                                'event'       => 'created',
                                'appeal_type' => 'electronic',
                                'claimid'     => 'CLM-001',
                            ],
                        ],
                    ],
                ],
            ];

            $dto = WebhookPayloadDTO::fromArray($data);

            expect($dto->utcTime)->toBe('2026-03-13T12:00:00Z');
            expect($dto->acctNumber)->toBe('ACCT-001');
            expect($dto->remoteAcctNumber)->toBe('REMOTE-001');
            expect($dto->events)->toHaveCount(2);

            expect($dto->events[0])->toBeInstanceOf(WebhookEventDTO::class);
            expect($dto->events[0]->eventType)->toBe('enroll');
            expect($dto->events[0]->enroll)->toBeInstanceOf(WebhookEnrollEventDTO::class);
            expect($dto->events[0]->enroll->enrollId)->toBe('ENR-001');

            expect($dto->events[1])->toBeInstanceOf(WebhookEventDTO::class);
            expect($dto->events[1]->eventType)->toBe('appeal');
            expect($dto->events[1]->appeal)->toBeInstanceOf(WebhookAppealEventDTO::class);
            expect($dto->events[1]->appeal->appealId)->toBe('APL-001');
        });

        it('throws exception when UTCTime is missing', function () {
            WebhookPayloadDTO::fromArray(['acct_number' => 'ACCT-001']);
        })->throws(InvalidArgumentException::class, 'UTCTime is required.');

        it('throws exception when acct_number is missing', function () {
            WebhookPayloadDTO::fromArray(['UTCTime' => '2026-03-13T12:00:00Z']);
        })->throws(InvalidArgumentException::class, 'acct_number is required.');

        it('handles empty events array', function () {
            $dto = WebhookPayloadDTO::fromArray([
                'UTCTime'     => '2026-03-13T12:00:00Z',
                'acct_number' => 'ACCT-001',
                'events'      => [],
            ]);

            expect($dto->events)->toBe([]);
        });

        it('handles missing events key', function () {
            $dto = WebhookPayloadDTO::fromArray([
                'UTCTime'     => '2026-03-13T12:00:00Z',
                'acct_number' => 'ACCT-001',
            ]);

            expect($dto->events)->toBe([]);
        });
    });

    describe('fromJsonString', function () {
        it('creates a DTO from a JSON string', function () {
            $json = json_encode([
                'UTCTime'            => '2026-03-13T12:00:00Z',
                'acct_number'        => 'ACCT-001',
                'remote_acct_number' => 'REMOTE-001',
                'events'             => [
                    [
                        'eventid'    => 'EVT-001',
                        'event_type' => 'enroll',
                        'event_time' => '2026-03-13T11:00:00Z',
                        'event_data' => [
                            'enroll' => [
                                'enrollid'    => 'ENR-001',
                                'event'       => 'enrolled',
                                'enroll_type' => 'era',
                            ],
                        ],
                    ],
                ],
            ]);

            $dto = WebhookPayloadDTO::fromJsonString($json);

            expect($dto->utcTime)->toBe('2026-03-13T12:00:00Z');
            expect($dto->acctNumber)->toBe('ACCT-001');
            expect($dto->remoteAcctNumber)->toBe('REMOTE-001');
            expect($dto->events)->toHaveCount(1);
            expect($dto->events[0]->enroll->enrollId)->toBe('ENR-001');
        });

        it('throws exception for invalid JSON', function () {
            WebhookPayloadDTO::fromJsonString('not valid json');
        })->throws(InvalidArgumentException::class, 'Invalid JSON string provided.');

        it('throws exception for non-object JSON', function () {
            WebhookPayloadDTO::fromJsonString('"just a string"');
        })->throws(InvalidArgumentException::class, 'Invalid JSON string provided.');

        it('throws exception when required fields are missing in JSON', function () {
            WebhookPayloadDTO::fromJsonString('{"acct_number": "ACCT-001"}');
        })->throws(InvalidArgumentException::class, 'UTCTime is required.');
    });
});
