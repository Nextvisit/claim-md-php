<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

use DateTime;
use InvalidArgumentException;

/**
 * Class ERADTO
 *
 * Data Transfer Object for ERA (Electronic Remittance Advice) information.
 */
readonly class ERADTO
{
    /**
     * ERADTO constructor.
     *
     * Can easily construct specific fields. For example:
     *  new ERADTO(
     *      fieldName: "value"
     *  )
     *
     * @param string|null $checkDate The check date in mm-dd-yyyy format.
     * @param string|null $receivedDate The received date in mm-dd-yyyy format, or 'today'/'yesterday'.
     * @param string|null $receivedAfterDate The received after date in mm-dd-yyyy format, or 'today'/'yesterday'.
     * @param string|null $checkNumber The check number.
     * @param string|null $checkAmount The check amount.
     * @param string|null $payerId The payer ID.
     * @param string|null $npi The National Provider Identifier.
     * @param string|null $taxId The Tax Identification Number.
     * @param string|null $newOnly Flag for new records only ('0' or '1').
     * @param string|null $eraId The ERA ID.
     * @param string|null $page The page number.
     *
     * @throws InvalidArgumentException If date format is invalid or newOnly is not '0' or '1'.
     */
    public function __construct(
        public ?string $checkDate = null,
        public ?string $receivedDate = null,
        public ?string $receivedAfterDate = null,
        public ?string $checkNumber = null,
        public ?string $checkAmount = null,
        public ?string $payerId = null,
        public ?string $npi = null,
        public ?string $taxId = null,
        public ?string $newOnly = null,
        public ?string $eraId = null,
        public ?string $page = null
    ) {
        if ($this->checkDate) {
            $this->validateDateFormat($this->checkDate, 'checkDate');
        }

        if ($this->receivedDate) {
            $this->validateDateFormat($this->receivedDate, 'receivedDate', true);
        }

        if ($this->receivedAfterDate) {
            $this->validateDateFormat($this->receivedAfterDate, 'receivedAfterDate', true);
        }

        if ($this->newOnly !== null) {
            $this->validateNewOnly();
        }
    }

    /**
     * Validates the date format.
     *
     * @param string $date The date to validate.
     * @param string $fieldName The name of the field being validated.
     * @param bool $allowTodayYesterday Whether to allow 'today' and 'yesterday' as valid inputs.
     *
     * @throws InvalidArgumentException If the date format is invalid.
     */
    private function validateDateFormat(string $date, string $fieldName, bool $allowTodayYesterday = false): void
    {
        if ($allowTodayYesterday && in_array(strtolower($date), ['today', 'yesterday'], true)) {
            return;
        }

        $d = DateTime::createFromFormat('m-d-Y', $date);
        if (!$d || $d->format('m-d-Y') !== $date) {
            throw new InvalidArgumentException("$fieldName must be in mm-dd-yyyy format or 'today'/'yesterday'");
        }
    }

    /**
     * Validates the newOnly field.
     *
     * @throws InvalidArgumentException If newOnly is not '0' or '1'.
     */
    private function validateNewOnly(): void
    {
        if (!in_array($this->newOnly, ['0', '1'], true)) {
            throw new InvalidArgumentException("newOnly must be either '1' (true) or '0' (false)");
        }
    }

    /**
     * Converts the DTO to an array.
     *
     * @return array The DTO data as an array.
     */
    public function toArray(): array
    {
        return array_filter([
            'CheckDate'           => $this->checkDate,
            'ReceivedDate'        => $this->receivedDate,
            'ReceivedAfterDate'   => $this->receivedAfterDate,
            'CheckNumber'         => $this->checkNumber,
            'CheckAmount'         => $this->checkAmount,
            'PayerID'             => $this->payerId,
            'NPI'                 => $this->npi,
            'TaxID'               => $this->taxId,
            'NewOnly'             => $this->newOnly,
            'ERAID'               => $this->eraId,
            'Page'                => $this->page,
        ], fn($value) => $value !== null);
    }

    /**
     * Creates an ERADTO instance from an array.
     *
     * @param array $data The data array to create the ERADTO from.
     *
     * @return self A new ERADTO instance.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            checkDate: $data['CheckDate'] ?? null,
            receivedDate: $data['ReceivedDate'] ?? null,
            receivedAfterDate: $data['ReceivedAfterDate'] ?? null,
            checkNumber: $data['CheckNumber'] ?? null,
            checkAmount: $data['CheckAmount'] ?? null,
            payerId: $data['PayerID'] ?? null,
            npi: $data['NPI'] ?? null,
            taxId: $data['TaxID'] ?? null,
            newOnly: $data['NewOnly'] ?? null,
            eraId: $data['ERAID'] ?? null,
            page: $data['Page'] ?? null
        );
    }
}