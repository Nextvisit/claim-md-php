<?php

namespace Nextvisit\ClaimMD\DTO;

use InvalidArgumentException;

/**
 * Class WebhookAppealEventDTO
 *
 * Data Transfer Object for appeal event data within a webhook payload.
 */
readonly class WebhookAppealEventDTO
{
    /**
     * @param string $appealId Unique identifier for this appeal
     * @param string $event Appeal event status
     * @param string|null $eventDetail Further details regarding this event
     * @param string|null $claimId Unique Claim.MD ID for the associated claim
     * @param string|null $remoteClaimId Unique ID assigned by user for the associated claim
     * @param string|null $appealType Appeal delivery method
     * @param string|null $serviceFee Service fees associated with this appeal event
     * @param string|null $pages Number of pages in the appeal
     * @param string|null $formId Unique ID for appeal form selected
     * @param string|null $formName Name of appeal form used
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string  $appealId,
        public string  $event,
        public ?string $eventDetail = null,
        public ?string $claimId = null,
        public ?string $remoteClaimId = null,
        public ?string $appealType = null,
        public ?string $serviceFee = null,
        public ?string $pages = null,
        public ?string $formId = null,
        public ?string $formName = null
    ) {
        $this->validateRequiredFields();
        $this->validateEvent();
        $this->validateAppealType();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->appealId)) {
            throw new InvalidArgumentException('appealId is required.');
        }
        if (empty($this->event)) {
            throw new InvalidArgumentException('event is required.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateEvent(): void
    {
        $validEvents = ['created', 'mailed', 'update', 'faxed', 'transmitted', 'failure'];
        if (!in_array($this->event, $validEvents, true)) {
            throw new InvalidArgumentException('event must be one of: ' . implode(', ', $validEvents));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateAppealType(): void
    {
        if ($this->appealType === null) {
            return;
        }

        $validTypes = ['electronic', 'mail', 'fax', 'download'];
        if (!in_array($this->appealType, $validTypes, true)) {
            throw new InvalidArgumentException('appealType must be one of: ' . implode(', ', $validTypes));
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'appealid'       => $this->appealId,
            'event'          => $this->event,
            'event_detail'   => $this->eventDetail,
            'claimid'        => $this->claimId,
            'remote_claimid' => $this->remoteClaimId,
            'appeal_type'    => $this->appealType,
            'service_fee'    => $this->serviceFee,
            'pages'          => $this->pages,
            'formid'         => $this->formId,
            'form_name'      => $this->formName,
        ], fn($value) => $value !== null);
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            appealId: $data['appealid'] ?? throw new InvalidArgumentException('appealid is required.'),
            event: $data['event'] ?? throw new InvalidArgumentException('event is required.'),
            eventDetail: $data['event_detail'] ?? null,
            claimId: $data['claimid'] ?? null,
            remoteClaimId: $data['remote_claimid'] ?? null,
            appealType: $data['appeal_type'] ?? null,
            serviceFee: $data['service_fee'] ?? null,
            pages: $data['pages'] ?? null,
            formId: $data['formid'] ?? null,
            formName: $data['form_name'] ?? null,
        );
    }
}
