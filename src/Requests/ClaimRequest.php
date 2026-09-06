<?php

namespace Nextvisit\ClaimMD\Requests;

use Generator;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Nextvisit\ClaimMD\Client;
use Nextvisit\ClaimMD\DTO\ClaimAppealDTO;
use Nextvisit\ClaimMD\Exceptions\ApiException;
use Nextvisit\ClaimMD\Exceptions\ClaimMDException;

/**
 * Class ClaimRequest
 * Handles various claim-related operations such as archiving, modifications, appeals, and notes.
 */
class ClaimRequest
{
    private const string ARCHIVE_ENDPOINT = '/services/archive/';
    private const string MODIFY_ENDPOINT = '/services/modify/';
    private const string APPEAL_ENDPOINT = '/services/appeal/';
    private const string NOTES_ENDPOINT = '/services/notes/';
    private const string CLAIM_DATA_ENDPOINT = '/services/claimdata/';

    /**
     * ClaimRequest constructor.
     *
     * @param Client $client The client used for making HTTP requests.
     */
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Archives one or more claims.
     *
     * @param string|list<string> $claimId Claim ID or a non-empty list of claim IDs.
     *
     * @return array The response from the server after the request is made.
     * @throws ClaimMDException If the API returns an error response.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function archive(string|array $claimId): array
    {
        if (is_array($claimId)) {
            if ($claimId === [] || !array_is_list($claimId)) {
                throw new InvalidArgumentException('Claim IDs must be a non-empty list of strings');
            }
            foreach ($claimId as $id) {
                if (!is_string($id) || $id === '') {
                    throw new InvalidArgumentException('Each claim ID must be a non-empty string');
                }
            }

            return $this->client->sendRequest('POST', self::ARCHIVE_ENDPOINT, ['claimid' => $claimId], repeatFormFields: true);
        }

        return $this->client->sendRequest('POST', self::ARCHIVE_ENDPOINT, ['claimid' => $claimId]);
    }

    public function downloadTransmittedClaims(
        string $transmitDate,
        string $claimForm,
        ?string $billNpi = null,
        ?string $billTaxId = null,
        ?string $payerId = null,
        int $page = 0
    ): string {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $transmitDate)) {
            throw new InvalidArgumentException('Transmit date must be in the format yyyy-mm-dd');
        }
        $parts = explode('-', $transmitDate);
        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            throw new InvalidArgumentException('Transmit date must be a valid calendar date in the format yyyy-mm-dd');
        }
        if (!in_array($claimForm, ['1500', 'ub', 'dental'], true)) {
            throw new InvalidArgumentException('Claim form must be one of: 1500, ub, dental');
        }
        if ($page < 0) {
            throw new InvalidArgumentException('Page must be zero or greater');
        }

        return $this->client->sendX12Request('POST', self::CLAIM_DATA_ENDPOINT, array_filter([
            'transmit_date' => $transmitDate,
            'claim_form' => $claimForm,
            'bill_npi' => $billNpi,
            'bill_taxid' => $billTaxId,
            'payerid' => $payerId,
            'pg' => $page,
        ], fn($value) => $value !== null));
    }

    /** @return Generator<int, string> */
    public function downloadAllTransmittedClaims(
        string $transmitDate,
        string $claimForm,
        ?string $billNpi = null,
        ?string $billTaxId = null,
        ?string $payerId = null
    ): Generator {
        for ($page = 0; ; $page++) {
            try {
                $x12 = $this->downloadTransmittedClaims($transmitDate, $claimForm, $billNpi, $billTaxId, $payerId, $page);
            } catch (ApiException $e) {
                if ($e->getStatusCode() === 200 && $e->getApiErrorCodes() === ['711']) {
                    return;
                }
                throw $e;
            }

            yield $page => $x12;
        }
    }

    /**
     * Retrieves a list of modifications based on provided parameters.
     *
     * @param string|null $modId     Modification ID to filter the modifications.
     * @param string|null $claimMdId Claim MD ID to filter the modifications.
     * @param string|null $field     Specific field to filter the modifications.
     *
     * @return array An array of modifications matching the specified criteria.
     * @throws ClaimMDException If the API returns an error response.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function listModifications(?string $modId = null, ?string $claimMdId = null, ?string $field = null): array
    {
        return $this->client->sendRequest('POST', self::MODIFY_ENDPOINT, array_filter(['ModID' => $modId, 'ClaimMD_ID' => $claimMdId, 'Field' => $field], fn($value) => $value !== null));
    }

    /**
     * Submits an appeal request for a claim.
     *
     * @param array|ClaimAppealDTO $claimAppeal Array or Data Transfer Object containing claim appeal details.
     *
     * @return array The response from the appeal endpoint.
     * @throws ClaimMDException If the API returns an error response.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function appeal(array|ClaimAppealDTO $claimAppeal): array
    {
        if ($claimAppeal instanceof ClaimAppealDTO) {
            return $this->client->sendRequest('POST', self::APPEAL_ENDPOINT, $claimAppeal->toArray());
        }
        return $this->client->sendRequest('POST', self::APPEAL_ENDPOINT, $claimAppeal);
    }

    /**
     * Retrieves a list of notes based on provided parameters.
     *
     * @param string|null $noteId    Note ID to filter the notes.
     * @param string|null $claimMdId Claim MD ID to filter the notes.
     *
     * @return array An array of notes matching the specified criteria.
     * @throws ClaimMDException If the API returns an error response.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function notes(?string $noteId = null, ?string $claimMdId = null): array
    {
        return $this->client->sendRequest('POST', self::NOTES_ENDPOINT, array_filter(['ClaimMD_ID' => $claimMdId, 'NoteID' => $noteId], fn($value) => $value !== null));
    }
}
