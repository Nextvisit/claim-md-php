<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use Generator;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Nextvisit\ClaimMDWrapper\Client;

/**
 * Class ResponseRequest
 *
 * Handles response-related requests to the Claim.MD service.
 */
class ResponseRequest
{
    /**
     * The endpoint for response-related requests.
     */
    private const RESPONSE_ENDPOINT = '/services/response/';

    /**
     * ResponseRequest constructor.
     *
     * @param Client $client The HTTP client for making requests
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Fetch responses from the API
     *
     * This method retrieves responses from the Claim.MD API. It can fetch responses
     * after a specific ResponseID and optionally for a specific claim.
     *
     * @param string $responseId The ResponseID to fetch responses after (use '0' for first request)
     * @param string|null $claimId Optional ClaimID to fetch responses for a specific claim
     *
     * @return array The API response containing the fetched responses
     *
     * @throws InvalidArgumentException If the responseId is empty
     * @throws GuzzleException If there's an HTTP request failure during the API call
     */
    public function fetchResponses(string $responseId, ?string $claimId = null): array
    {
        if ($responseId === '') {
            throw new InvalidArgumentException('ResponseID cannot be empty');
        }

        $data = [
            'ResponseID' => $responseId,
        ];

        if ($claimId !== null) {
            $data['ClaimID'] = $claimId;
        }

        return $this->client->sendRequest('POST', self::RESPONSE_ENDPOINT, $data);
    }

    /**
     * Fetch all responses, handling pagination automatically
     *
     * This method retrieves all responses from the Claim.MD API, automatically
     * handling pagination. It yields each page of responses as they are fetched.
     *
     * @param string|null $claimId Optional ClaimID to fetch responses for a specific claim
     *
     * @return Generator A generator that yields each page of responses
     *
     * @throws GuzzleException If there's an HTTP request failure during any of the API calls
     */
    public function fetchAllResponses(?string $claimId = null): Generator
    {
        $responseId = '0';

        do {
            $response = $this->fetchResponses($responseId, $claimId);

            yield $response;

            $responseId = $response['last_responseid'] ?? null;
        } while ($responseId !== null);
    }
}