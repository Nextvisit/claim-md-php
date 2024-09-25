<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;

/**
 * Class PayerRequest
 *
 * Handles payer-related requests to the Claim.MD service.
 */
class PayerRequest
{
    /**
     * The endpoint for payer-related requests.
     */
    private const PAYER_ENDPOINT = '/services/payerlist/';

    /**
     * PayerRequest constructor.
     *
     * @param Client $client The HTTP client for making requests
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Get Payer List and available services.
     *
     * This method retrieves a list of payers and their available services from the Claim.MD API.
     * It can optionally filter the results by payer ID or payer name.
     *
     * @param string|null $payerId Optional payer ID to filter the payers.
     * @param string|null $payerName Optional payer name to filter the payers.
     * @return array An array containing the payer list and available services.
     * @throws GuzzleException If there's an HTTP request failure.
     */
    public function listPayer(?string $payerId = null, ?string $payerName = null): array
    {
        return $this->client->sendRequest(
            'POST',
            self::PAYER_ENDPOINT,
            array_filter(
                ['payerid' => $payerId, 'payer_name' => $payerName],
                fn($value) => $value !== null
            )
        );
    }
}