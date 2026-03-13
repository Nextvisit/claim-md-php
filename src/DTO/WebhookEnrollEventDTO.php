<?php

namespace Nextvisit\ClaimMD\DTO;

use InvalidArgumentException;

/**
 * Class WebhookEnrollEventDTO
 *
 * Data Transfer Object for enrollment event data within a webhook payload.
 */
readonly class WebhookEnrollEventDTO
{
    /**
     * @param string $enrollId Unique identifier for this enrollment
     * @param string $event Enrollment event status
     * @param string $enrollType Enrollment type
     * @param string|null $eventDetail Further details regarding this event
     * @param string|null $provNpi Provider NPI Number
     * @param string|null $provTaxId Provider Tax ID
     * @param string|null $provId Provider A-Typical ID
     * @param string|null $payerId Payer ID
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string  $enrollId,
        public string  $event,
        public string  $enrollType,
        public ?string $eventDetail = null,
        public ?string $provNpi = null,
        public ?string $provTaxId = null,
        public ?string $provId = null,
        public ?string $payerId = null
    ) {
        $this->validateRequiredFields();
        $this->validateEvent();
        $this->validateEnrollType();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->enrollId)) {
            throw new InvalidArgumentException('enrollId is required.');
        }
        if (empty($this->event)) {
            throw new InvalidArgumentException('event is required.');
        }
        if (empty($this->enrollType)) {
            throw new InvalidArgumentException('enrollType is required.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEvent(): void
    {
        $validEvents = ['enrolled', 'received', 'completed', 'rejected'];
        if (!in_array($this->event, $validEvents, true)) {
            throw new InvalidArgumentException('event must be one of: ' . implode(', ', $validEvents));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEnrollType(): void
    {
        $validTypes = ['era', '1500', 'ub', 'elig', 'attach'];
        if (!in_array($this->enrollType, $validTypes, true)) {
            throw new InvalidArgumentException('enrollType must be one of: ' . implode(', ', $validTypes));
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'enrollid'     => $this->enrollId,
            'event'        => $this->event,
            'event_detail' => $this->eventDetail,
            'enroll_type'  => $this->enrollType,
            'prov_npi'     => $this->provNpi,
            'prov_taxid'   => $this->provTaxId,
            'prov_id'      => $this->provId,
            'payerid'      => $this->payerId,
        ], fn($value) => $value !== null);
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enrollId: $data['enrollid'] ?? throw new InvalidArgumentException('enrollid is required.'),
            event: $data['event'] ?? throw new InvalidArgumentException('event is required.'),
            enrollType: $data['enroll_type'] ?? throw new InvalidArgumentException('enroll_type is required.'),
            eventDetail: $data['event_detail'] ?? null,
            provNpi: $data['prov_npi'] ?? null,
            provTaxId: $data['prov_taxid'] ?? null,
            provId: $data['prov_id'] ?? null,
            payerId: $data['payerid'] ?? null,
        );
    }
}
