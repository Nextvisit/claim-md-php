<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;

class PayerRequest
{
    private const PAYER_ENDPOINT = '/services/payerlist/';

    public function __construct(private readonly Client $client) {}

    /**
     * Get Payer List and available services.
     *
     * @param string|null $payerId Optional payer ID to filter the payers.
     * @param string|null $payerName Optional payer name to filter the payers.
     * @return array
     * @throws GuzzleException HTTP Request Failure
     */
    public function listPayer(?string $payerId = null, ?string $payerName = null): array
    {
        return $this->client->sendRequest('POST', self::PAYER_ENDPOINT, array_filter(['payerid' => $payerId, 'payer_name' => $payerName], fn($value) => $value !== null));
    }
}