<?php

namespace Nextvisit\ClaimMDWrapper\DTO;

readonly class ERADTO
{
    /**
     * Can easily construct specific fields. For example:
     *  new ERADTO(
     *      fieldName: "value"
     *  )
     *
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

    private function validateDateFormat(string $date, string $fieldName, bool $allowTodayYesterday = false): void
    {
        if ($allowTodayYesterday && in_array(strtolower($date), ['today', 'yesterday'], true)) {
            return;
        }

        $d = \DateTime::createFromFormat('m-d-Y', $date);
        if (!$d || $d->format('m-d-Y') !== $date) {
            throw new \InvalidArgumentException("$fieldName must be in mm-dd-yyyy format or 'today'/'yesterday'");
        }
    }

    private function validateNewOnly(): void
    {
        if (!in_array($this->newOnly, ['0', '1'], true)) {
            throw new \InvalidArgumentException("newOnly must be either '1' (true) or '0' (false)");
        }
    }

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
