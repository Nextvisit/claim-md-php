<?php

namespace Nextvisit\ClaimMD\DTO;

use InvalidArgumentException;

/**
 * Class WebhookEventDTO
 *
 * Data Transfer Object for a single event within a webhook payload.
 */
readonly class WebhookEventDTO
{
    /**
     * @param string $eventId Unique identifier for this event
     * @param string $eventType The type of event (enroll or appeal)
     * @param string|null $eventTime UTC time of event
     * @param WebhookEnrollEventDTO|null $enroll Enrollment event data
     * @param WebhookAppealEventDTO|null $appeal Appeal event data
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string                  $eventId,
        public string                  $eventType,
        public ?string                 $eventTime = null,
        public ?WebhookEnrollEventDTO  $enroll = null,
        public ?WebhookAppealEventDTO  $appeal = null
    ) {
        $this->validateRequiredFields();
        $this->validateEventType();
        $this->validateEventData();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->eventId)) {
            throw new InvalidArgumentException('eventId is required.');
        }
        if (empty($this->eventType)) {
            throw new InvalidArgumentException('eventType is required.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEventType(): void
    {
        $validTypes = ['enroll', 'appeal'];
        if (!in_array($this->eventType, $validTypes, true)) {
            throw new InvalidArgumentException('eventType must be one of: ' . implode(', ', $validTypes));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEventData(): void
    {
        if ($this->eventType === 'enroll' && $this->enroll === null) {
            throw new InvalidArgumentException('enroll event data is required when eventType is "enroll".');
        }
        if ($this->eventType === 'appeal' && $this->appeal === null) {
            throw new InvalidArgumentException('appeal event data is required when eventType is "appeal".');
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = array_filter([
            'eventid'    => $this->eventId,
            'event_type' => $this->eventType,
            'event_time' => $this->eventTime,
        ], fn($value) => $value !== null);

        $eventData = array_filter([
            'enroll' => $this->enroll?->toArray(),
            'appeal' => $this->appeal?->toArray(),
        ], fn($value) => $value !== null);

        if (!empty($eventData)) {
            $result['event_data'] = $eventData;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $eventData = $data['event_data'] ?? [];

        return new self(
            eventId: $data['eventid'] ?? throw new InvalidArgumentException('eventid is required.'),
            eventType: $data['event_type'] ?? throw new InvalidArgumentException('event_type is required.'),
            eventTime: $data['event_time'] ?? null,
            enroll: isset($eventData['enroll']) ? WebhookEnrollEventDTO::fromArray($eventData['enroll']) : null,
            appeal: isset($eventData['appeal']) ? WebhookAppealEventDTO::fromArray($eventData['appeal']) : null,
        );
    }
}
