<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;

/**
 * Class ClaimRequest
 *
 * Handles various claim-related operations such as archiving, modifications, appeals, and notes.
 */
class ClaimRequest
{
    private const ARCHIVE_ENDPOINT = '/services/archive/';
    private const MODIFY_ENDPOINT = '/services/modify/';
    private const APPEAL_ENDPOINT = '/services/appeal/';
    private const NOTES_ENDPOINT = '/services/notes/';

    /**
     * ClaimRequest constructor.
     *
     * @param Client $client The client used for making HTTP requests.
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Archives a claim with the given claim ID.
     *
     * @param string $claimId The ID of the claim to be archived.
     * @return array The response from the server after the request is made.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function archive(string $claimId): array
    {
        return $this->client->sendRequest('POST', self::ARCHIVE_ENDPOINT, ['claimid' => $claimId]);
    }

    /**
     * Retrieves a list of modifications based on provided parameters.
     *
     * @param string|null $modId Modification ID to filter the modifications.
     * @param string|null $claimMdId Claim MD ID to filter the modifications.
     * @param string|null $field Specific field to filter the modifications.
     * @return array An array of modifications matching the specified criteria.
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
     * @return array The response from the appeal endpoint.
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
     * @param string|null $noteId Note ID to filter the notes.
     * @param string|null $claimMdId Claim MD ID to filter the notes.
     * @return array An array of notes matching the specified criteria.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function notes(?string $noteId = null, ?string $claimMdId = null): array
    {
        return $this->client->sendRequest('POST', self::NOTES_ENDPOINT, array_filter(['ClaimMD_ID' => $claimMdId, 'NoteID' => $noteId], fn($value) => $value !== null));
    }
}