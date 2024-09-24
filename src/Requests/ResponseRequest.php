<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use Generator;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Nextvisit\ClaimMDWrapper\Client;

class ResponseRequest
{
    private const RESPONSE_ENDPOINT = '/services/response/';

    public function __construct(private readonly Client $client) {}

    /**
     * Fetch responses from the API
     *
     * @param string $responseId The ResponseID to fetch responses after (use '0' for first request)
     * @param string|null $claimId Optional ClaimID to fetch responses for a specific claim
     * @return array The API response
     * @throws InvalidArgumentException If the responseId is empty
     * @throws GuzzleException HTTP Request Failure
     */
    public function fetchResponses(string $responseId, ?string $claimId = null): array
    {
        if (empty($responseId)) {
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
     * @param string|null $claimId Optional ClaimID to fetch responses for a specific claim
     * @return Generator
     * @throws GuzzleException
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