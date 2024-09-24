<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

readonly class ClaimAppealDTO
{
    /**
     * Can easily construct specific fields. For example:
     *  new ClaimAppealDTO(
     *      fieldName: "value"
     *  )
     *
     */
    public function __construct(
        public ?string $claimId = null,
        public ?string $remoteClaimId = null,
        public ?string $contactName = null,
        public ?string $contactTitle = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?string $contactFax = null,
        public ?string $contactAddr1 = null,
        public ?string $contactAddr2 = null,
        public ?string $contactCity = null,
        public ?string $contactState = null,
        public ?string $contactZip = null
    ) {
        $this->validateRequiredFields();
        $this->validateEmail();
        $this->validatePhoneNumber($this->contactPhone, 'contactPhone');
        $this->validatePhoneNumber($this->contactFax, 'contactFax');
        $this->validateStateCode();
    }

    private function validateRequiredFields(): void
    {
        if (empty($this->claimId) && empty($this->remoteClaimId)) {
            throw new \InvalidArgumentException('Either claimId or remoteClaimId must be provided.');
        }
    }

    private function validateEmail(): void
    {
        if ($this->contactEmail && !filter_var($this->contactEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('contactEmail must be a valid email address.');
        }
    }

    private function validatePhoneNumber(?string $phoneNumber, string $fieldName): void
    {
        if ($phoneNumber && !preg_match('/^\+?[0-9\-\(\)\s]+$/', $phoneNumber)) {
            throw new \InvalidArgumentException("$fieldName must be a valid phone number.");
        }
    }

    private function validateStateCode(): void
    {
        if ($this->contactState && !preg_match('/^[A-Z]{2}$/', $this->contactState)) {
            throw new \InvalidArgumentException('contactState must be a valid two-letter state code.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'claimid'        => $this->claimId,
            'remote_claimid' => $this->remoteClaimId,
            'contact_name'   => $this->contactName,
            'contact_title'  => $this->contactTitle,
            'contact_email'  => $this->contactEmail,
            'contact_phone'  => $this->contactPhone,
            'contact_fax'    => $this->contactFax,
            'contact_addr_1' => $this->contactAddr1,
            'contact_addr_2' => $this->contactAddr2,
            'contact_city'   => $this->contactCity,
            'contact_state'  => $this->contactState,
            'contact_zip'    => $this->contactZip,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            claimId: $data['claimid'] ?? null,
            remoteClaimId: $data['remote_claimid'] ?? null,
            contactName: $data['contact_name'] ?? null,
            contactTitle: $data['contact_title'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactFax: $data['contact_fax'] ?? null,
            contactAddr1: $data['contact_addr_1'] ?? null,
            contactAddr2: $data['contact_addr_2'] ?? null,
            contactCity: $data['contact_city'] ?? null,
            contactState: $data['contact_state'] ?? null,
            contactZip: $data['contact_zip'] ?? null
        );
    }
}
