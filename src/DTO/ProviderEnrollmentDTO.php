<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

readonly class ProviderEnrollmentDTO
{
    /**
     * Can easily construct specific fields. For example:
     *  new ProviderEnrollmentDTO(
     *      fieldName: "value"
     *  )
     *
     */
    public function __construct(
        public string  $payerId,
        public string  $enrollType,
        public string  $provTaxId,
        public ?string $provNpi = null,
        public ?string $provNameLast = null,
        public ?string $provNameFirst = null,
        public ?string $provNameMiddle = null,
        public ?string $contact = null,
        public ?string $contactTitle = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?string $contactFax = null,
        public ?string $provId = null,
        public ?string $provAddr1 = null,
        public ?string $provAddr2 = null,
        public ?string $provCity = null,
        public ?string $provState = null,
        public ?string $provZip = null
    ) {
        $this->validateRequiredFields();
        $this->validateEnrollType();
        $this->validateSituationalFields();
        if ($this->contactEmail) {
            $this->validateEmail($this->contactEmail);
        }
        if ($this->provState) {
            $this->validateStateCode($this->provState, 'provState');
        }
    }

    private function validateRequiredFields(): void
    {
        if (empty($this->payerId)) {
            throw new \InvalidArgumentException('payerId is required.');
        }
        if (empty($this->enrollType)) {
            throw new \InvalidArgumentException('enrollType is required.');
        }
        if (empty($this->provTaxId)) {
            throw new \InvalidArgumentException('provTaxId is required.');
        }
    }

    private function validateEnrollType(): void
    {
        $validEnrollTypes = ['era', '1500', 'ub', 'elig', 'attach'];
        if (!in_array($this->enrollType, $validEnrollTypes, true)) {
            throw new \InvalidArgumentException('enrollType must be one of: ' . implode(', ', $validEnrollTypes));
        }
    }

    private function validateSituationalFields(): void
    {
        if (empty($this->provNpi)) {
            if (empty($this->provNameLast)) {
                throw new \InvalidArgumentException('provNameLast is required when provNpi is not provided.');
            }
            // Assuming provider is an individual if provNameFirst is provided
            if ($this->provNameLast && empty($this->provNameFirst)) {
                throw new \InvalidArgumentException('provNameFirst is required when provNpi is not provided and the provider is an individual.');
            }
        }
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('contactEmail must be a valid email address.');
        }
    }

    private function validateStateCode(string $state, string $fieldName): void
    {
        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new \InvalidArgumentException("$fieldName must be a valid two-letter state code.");
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'payerid'        => $this->payerId,
            'enroll_type'    => $this->enrollType,
            'prov_taxid'     => $this->provTaxId,
            'prov_npi'       => $this->provNpi,
            'prov_name_l'    => $this->provNameLast,
            'prov_name_f'    => $this->provNameFirst,
            'prov_name_m'    => $this->provNameMiddle,
            'contact'        => $this->contact,
            'contact_title'  => $this->contactTitle,
            'contact_email'  => $this->contactEmail,
            'contact_phone'  => $this->contactPhone,
            'contact_fax'    => $this->contactFax,
            'prov_id'        => $this->provId,
            'prov_addr_1'    => $this->provAddr1,
            'prov_addr_2'    => $this->provAddr2,
            'prov_city'      => $this->provCity,
            'prov_state'     => $this->provState,
            'prov_zip'       => $this->provZip,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payerId: $data['payerid'] ?? throw new \InvalidArgumentException('payerid is required.'),
            enrollType: $data['enroll_type'] ?? throw new \InvalidArgumentException('enroll_type is required.'),
            provTaxId: $data['prov_taxid'] ?? throw new \InvalidArgumentException('prov_taxid is required.'),
            provNpi: $data['prov_npi'] ?? null,
            provNameLast: $data['prov_name_l'] ?? null,
            provNameFirst: $data['prov_name_f'] ?? null,
            provNameMiddle: $data['prov_name_m'] ?? null,
            contact: $data['contact'] ?? null,
            contactTitle: $data['contact_title'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactFax: $data['contact_fax'] ?? null,
            provId: $data['prov_id'] ?? null,
            provAddr1: $data['prov_addr_1'] ?? null,
            provAddr2: $data['prov_addr_2'] ?? null,
            provCity: $data['prov_city'] ?? null,
            provState: $data['prov_state'] ?? null,
            provZip: $data['prov_zip'] ?? null
        );
    }
}
