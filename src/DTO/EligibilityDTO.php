<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

use InvalidArgumentException;

/**
 * Class EligibilityDTO
 *
 * This class represents an Eligibility Data Transfer Object.
 */
readonly class EligibilityDTO
{
    /**
     * EligibilityDTO constructor.
     *
     * Can easily construct specific fields. For example:
     *  new EligibilityDTO(
     *      fieldName: "value"
     *  )
     *
     * @param string $insLastName Insured's last name
     * @param string $insFirstName Insured's first name
     * @param string $payerId Payer ID
     * @param string $patientRelationship Patient relationship code
     * @param string $serviceDate Service date in yyyymmdd format
     * @param string $providerNpi Provider NPI
     * @param string $providerTaxId Provider Tax ID
     * @param string|null $insMiddleName Insured's middle name
     * @param string|null $serviceCode Service code
     * @param string|null $procCode Procedure code
     * @param string|null $insNumber Insurance number
     * @param string|null $insDob Insured's date of birth in yyyymmdd format
     * @param string|null $insSex Insured's sex ('M' or 'F')
     * @param string|null $patLastName Patient's last name
     * @param string|null $patFirstName Patient's first name
     * @param string|null $patMiddleName Patient's middle name
     * @param string|null $patDob Patient's date of birth in yyyymmdd format
     * @param string|null $patSex Patient's sex ('M' or 'F')
     * @param string|null $provNameLast Provider's last name
     * @param string|null $provNameFirst Provider's first name
     * @param string|null $provTaxonomy Provider's taxonomy
     * @param string|null $provTaxIdType Provider's tax ID type ('E' or 'S')
     * @param string|null $provAddr1 Provider's address line 1
     * @param string|null $provCity Provider's city
     * @param string|null $provState Provider's state
     * @param string|null $provZip Provider's ZIP code
     */
    public function __construct(
        public string  $insLastName,
        public string  $insFirstName,
        public string  $payerId,
        public string  $patientRelationship,
        public string  $serviceDate,
        public string  $providerNpi,
        public string  $providerTaxId,
        public ?string $insMiddleName = null,
        public ?string $serviceCode = null,
        public ?string $procCode = null,
        public ?string $insNumber = null,
        public ?string $insDob = null,
        public ?string $insSex = null,
        public ?string $patLastName = null,
        public ?string $patFirstName = null,
        public ?string $patMiddleName = null,
        public ?string $patDob = null,
        public ?string $patSex = null,
        public ?string $provNameLast = null,
        public ?string $provNameFirst = null,
        public ?string $provTaxonomy = null,
        public ?string $provTaxIdType = null,
        public ?string $provAddr1 = null,
        public ?string $provCity = null,
        public ?string $provState = null,
        public ?string $provZip = null
    ) {
        $this->validateRequiredFields();
        $this->validateDateFormat($serviceDate, 'serviceDate');
        if ($insDob) $this->validateDateFormat($insDob, 'insDob');
        if ($patDob) $this->validateDateFormat($patDob, 'patDob');
        $this->validatePatientRelationship();
        if ($insSex) $this->validateSex($insSex, 'insSex');
        if ($patSex) $this->validateSex($patSex, 'patSex');
        if ($provTaxIdType) $this->validateProvTaxIdType();
    }

    /**
     * Validates that all required fields are not empty.
     *
     * @throws InvalidArgumentException If a required field is empty
     */
    private function validateRequiredFields(): void
    {
        $requiredFields = ['insLastName', 'insFirstName', 'payerId', 'patientRelationship', 'serviceDate', 'providerNpi', 'providerTaxId'];
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                throw new InvalidArgumentException("$field is required");
            }
        }
    }

    /**
     * Validates the date format of a given field.
     *
     * @param string $date The date to validate
     * @param string $fieldName The name of the field being validated
     * @throws InvalidArgumentException If the date format is invalid
     */
    private function validateDateFormat(string $date, string $fieldName): void
    {
        if (!preg_match('/^\d{8}$/', $date)) {
            throw new InvalidArgumentException("$fieldName must be in yyyymmdd format");
        }
    }

    /**
     * Validates the patient relationship field.
     *
     * @throws InvalidArgumentException If the patient relationship is invalid
     */
    private function validatePatientRelationship(): void
    {
        if (!in_array($this->patientRelationship, ['18', 'G8'])) {
            throw new InvalidArgumentException("patientRelationship must be either '18' or 'G8'");
        }
    }

    /**
     * Validates the sex field.
     *
     * @param string $sex The sex to validate
     * @param string $fieldName The name of the field being validated
     * @throws InvalidArgumentException If the sex is invalid
     */
    private function validateSex(string $sex, string $fieldName): void
    {
        if (!in_array($sex, ['M', 'F'])) {
            throw new InvalidArgumentException("$fieldName must be either 'M' or 'F'");
        }
    }

    /**
     * Validates the provider tax ID type.
     *
     * @throws InvalidArgumentException If the provider tax ID type is invalid
     */
    private function validateProvTaxIdType(): void
    {
        if (!in_array($this->provTaxIdType, ['E', 'S'])) {
            throw new InvalidArgumentException("provTaxIdType must be either 'E' or 'S'");
        }
    }

    /**
     * Converts the DTO to an array.
     *
     * @return array The DTO as an array
     */
    public function toArray(): array
    {
        return array_filter([
            'ins_name_l' => $this->insLastName,
            'ins_name_f' => $this->insFirstName,
            'payerid' => $this->payerId,
            'pat_rel' => $this->patientRelationship,
            'fdos' => $this->serviceDate,
            'prov_npi' => $this->providerNpi,
            'prov_taxid' => $this->providerTaxId,
            'ins_name_m' => $this->insMiddleName,
            'service_code' => $this->serviceCode,
            'proc_code' => $this->procCode,
            'ins_number' => $this->insNumber,
            'ins_dob' => $this->insDob,
            'ins_sex' => $this->insSex,
            'pat_name_l' => $this->patLastName,
            'pat_name_f' => $this->patFirstName,
            'pat_name_m' => $this->patMiddleName,
            'pat_dob' => $this->patDob,
            'pat_sex' => $this->patSex,
            'prov_name_l' => $this->provNameLast,
            'prov_name_f' => $this->provNameFirst,
            'prov_taxonomy' => $this->provTaxonomy,
            'prov_taxid_type' => $this->provTaxIdType,
            'prov_addr_1' => $this->provAddr1,
            'prov_city' => $this->provCity,
            'prov_state' => $this->provState,
            'prov_zip' => $this->provZip,
        ], fn($value) => $value !== null);
    }

    /**
     * Creates an EligibilityDTO from an array.
     *
     * @param array $data The array to create the DTO from
     * @return self The created EligibilityDTO
     * @throws InvalidArgumentException If a required field is missing
     */
    public static function fromArray(array $data): self
    {
        $requiredFields = ['ins_name_l', 'ins_name_f', 'payerid', 'pat_rel', 'fdos', 'prov_npi', 'prov_taxid'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }

        return new self(
            insLastName: $data['ins_name_l'],
            insFirstName: $data['ins_name_f'],
            payerId: $data['payerid'],
            patientRelationship: $data['pat_rel'],
            serviceDate: $data['fdos'],
            providerNpi: $data['prov_npi'],
            providerTaxId: $data['prov_taxid'],
            insMiddleName: $data['ins_name_m'] ?? null,
            serviceCode: $data['service_code'] ?? null,
            procCode: $data['proc_code'] ?? null,
            insNumber: $data['ins_number'] ?? null,
            insDob: $data['ins_dob'] ?? null,
            insSex: $data['ins_sex'] ?? null,
            patLastName: $data['pat_name_l'] ?? null,
            patFirstName: $data['pat_name_f'] ?? null,
            patMiddleName: $data['pat_name_m'] ?? null,
            patDob: $data['pat_dob'] ?? null,
            patSex: $data['pat_sex'] ?? null,
            provNameLast: $data['prov_name_l'] ?? null,
            provNameFirst: $data['prov_name_f'] ?? null,
            provTaxonomy: $data['prov_taxonomy'] ?? null,
            provTaxIdType: $data['prov_taxid_type'] ?? null,
            provAddr1: $data['prov_addr_1'] ?? null,
            provCity: $data['prov_city'] ?? null,
            provState: $data['prov_state'] ?? null,
            provZip: $data['prov_zip'] ?? null
        );
    }
}