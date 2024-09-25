<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

use InvalidArgumentException;

/**
 * Class ProviderEnrollmentDTO
 *
 * This class represents a Data Transfer Object for provider enrollment information.
 */
readonly class ProviderEnrollmentDTO
{
    /**
     * ProviderEnrollmentDTO constructor.
     *
     * Can easily construct specific fields. For example:
     *  new ProviderEnrollmentDTO(
     *      fieldName: "value"
     *  )
     *
     * @param string $payerId The payer ID
     * @param string $enrollType The enrollment type
     * @param string $provTaxId The provider tax ID
     * @param string|null $provNpi The provider NPI (optional)
     * @param string|null $provNameLast The provider's last name (optional)
     * @param string|null $provNameFirst The provider's first name (optional)
     * @param string|null $provNameMiddle The provider's middle name (optional)
     * @param string|null $contact The contact person (optional)
     * @param string|null $contactTitle The contact person's title (optional)
     * @param string|null $contactEmail The contact person's email (optional)
     * @param string|null $contactPhone The contact person's phone (optional)
     * @param string|null $contactFax The contact person's fax (optional)
     * @param string|null $provId The provider ID (optional)
     * @param string|null $provAddr1 The provider's address line 1 (optional)
     * @param string|null $provAddr2 The provider's address line 2 (optional)
     * @param string|null $provCity The provider's city (optional)
     * @param string|null $provState The provider's state (optional)
     * @param string|null $provZip The provider's ZIP code (optional)
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

    /**
     * Validates required fields.
     *
     * @throws InvalidArgumentException If any required field is empty.
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->payerId)) {
            throw new InvalidArgumentException('payerId is required.');
        }
        if (empty($this->enrollType)) {
            throw new InvalidArgumentException('enrollType is required.');
        }
        if (empty($this->provTaxId)) {
            throw new InvalidArgumentException('provTaxId is required.');
        }
    }

    /**
     * Validates the enrollment type.
     *
     * @throws InvalidArgumentException If the enrollment type is invalid.
     */
    private function validateEnrollType(): void
    {
        $validEnrollTypes = ['era', '1500', 'ub', 'elig', 'attach'];
        if (!in_array($this->enrollType, $validEnrollTypes, true)) {
            throw new InvalidArgumentException('enrollType must be one of: ' . implode(', ', $validEnrollTypes));
        }
    }

    /**
     * Validates situational fields.
     *
     * @throws InvalidArgumentException If situational field requirements are not met.
     */
    private function validateSituationalFields(): void
    {
        if (empty($this->provNpi)) {
            if (empty($this->provNameLast)) {
                throw new InvalidArgumentException('provNameLast is required when provNpi is not provided.');
            }
            // Assuming provider is an individual if provNameFirst is provided
            if ($this->provNameLast && empty($this->provNameFirst)) {
                throw new InvalidArgumentException('provNameFirst is required when provNpi is not provided and the provider is an individual.');
            }
        }
    }

    /**
     * Validates an email address.
     *
     * @param string $email The email address to validate.
     * @throws InvalidArgumentException If the email is invalid.
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('contactEmail must be a valid email address.');
        }
    }

    /**
     * Validates a state code.
     *
     * @param string $state The state code to validate.
     * @param string $fieldName The name of the field being validated.
     * @throws InvalidArgumentException If the state code is invalid.
     */
    private function validateStateCode(string $state, string $fieldName): void
    {
        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new InvalidArgumentException("$fieldName must be a valid two-letter state code.");
        }
    }

    /**
     * Converts the DTO to an array.
     *
     * @return array The DTO as an array.
     */
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

    /**
     * Creates a ProviderEnrollmentDTO from an array.
     *
     * @param array $data The array containing provider enrollment data.
     * @return self A new instance of ProviderEnrollmentDTO.
     * @throws InvalidArgumentException If required fields are missing.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            payerId: $data['payerid'] ?? throw new InvalidArgumentException('payerid is required.'),
            enrollType: $data['enroll_type'] ?? throw new InvalidArgumentException('enroll_type is required.'),
            provTaxId: $data['prov_taxid'] ?? throw new InvalidArgumentException('prov_taxid is required.'),
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