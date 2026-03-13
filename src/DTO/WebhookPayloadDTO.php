<?php

namespace Nextvisit\ClaimMD\DTO;

use InvalidArgumentException;

/**
 * Class WebhookPayloadDTO
 *
 * Data Transfer Object for inbound webhook payloads from Claim.MD.
 * Represents provider enrollment updates and appeal form creation/updates.
 */
readonly class WebhookPayloadDTO
{
    /**
     * @param string $utcTime UTC current time
     * @param string $acctNumber Claim.MD Account Number
     * @param string|null $remoteAcctNumber Customer assigned account number
     * @param WebhookEventDTO[] $events Array of webhook events
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string  $utcTime,
        public string  $acctNumber,
        public ?string $remoteAcctNumber = null,
        public array   $events = []
    ) {
        $this->validateRequiredFields();
        $this->validateEvents();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->utcTime)) {
            throw new InvalidArgumentException('utcTime is required.');
        }
        if (empty($this->acctNumber)) {
            throw new InvalidArgumentException('acctNumber is required.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEvents(): void
    {
        foreach ($this->events as $index => $event) {
            if (!$event instanceof WebhookEventDTO) {
                throw new InvalidArgumentException("Event at index $index must be an instance of WebhookEventDTO.");
            }
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = array_filter([
            'UTCTime'             => $this->utcTime,
            'acct_number'         => $this->acctNumber,
            'remote_acct_number'  => $this->remoteAcctNumber,
        ], fn($value) => $value !== null);

        $result['events'] = array_map(fn(WebhookEventDTO $event) => $event->toArray(), $this->events);

        return $result;
    }

    /**
     * Create a WebhookPayloadDTO from a JSON string.
     *
     * @param string $json The JSON string to parse
     * @return self
     * @throws InvalidArgumentException If the JSON is invalid
     */
    public static function fromJsonString(string $json): self
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON string provided.');
        }

        return self::fromArray($data);
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $events = array_map(
            fn(array $eventData) => WebhookEventDTO::fromArray($eventData),
            $data['events'] ?? []
        );

        return new self(
            utcTime: $data['UTCTime'] ?? throw new InvalidArgumentException('UTCTime is required.'),
            acctNumber: $data['acct_number'] ?? throw new InvalidArgumentException('acct_number is required.'),
            remoteAcctNumber: $data['remote_acct_number'] ?? null,
            events: $events,
        );
    }
}
