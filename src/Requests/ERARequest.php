<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

class ERARequest
{
    private const ERA_LIST_ENDPOINT = '/services/eralist/';
    private const ERA_835_ENDPOINT = '/services/era835/';
    private const ERA_PDF_ENDPOINT = '/services/erapdf/';
    private const ERA_JSON_ENDPOINT = '/services/eradata/';

    public function __construct(private readonly Client $client) {}

    /**
     * Retrieves the JSON representation of an electronic remittance advice.
     *
     * @param string $eraId The ID of the electronic remittance advice.
     * @return array The JSON representation of the electronic remittance advice.
     * @throws GuzzleException HTTP Request Failure
     */
    public function getJson(string $eraId): array
    {
        return $this->client->sendRequest('POST', self::ERA_JSON_ENDPOINT, ['eraid' => $eraId]);
    }

    /**
     * Retrieves a PDF based on the given era ID and optionally a PCN.
     *
     * @param string $eraId The ID of the era to get the PDF for.
     * @param string|null $pcn An optional parameter for the PCN.
     * @return array The response from the client request containing the PDF data (Base64 Encoded).
     * @throws GuzzleException HTTP Request Failure
     */
    public function getPDF(string $eraId, ?string $pcn = null): array
    {
        return $this->client->sendRequest('POST', self::ERA_PDF_ENDPOINT, array_filter(['eraid' => $eraId, 'pcn' => $pcn], fn($value) => $value !== null));
    }

    /**
     * Retrieves the 835 ERA (Electronic Remittance Advice) based on the provided ERA ID.
     *
     * @param string $eraId The identifier for the ERA to be retrieved.
     * @return array The 835 ERA data as an array.
     * @throws GuzzleException HTTP Request Failure
     */
    public function get835(string $eraId): array
    {
        return $this->client->sendRequest('POST', self::ERA_835_ENDPOINT, ['eraid' => $eraId]);
    }

    /**
     * Retrieves a list of electronic remittance advices.
     *
     * @param array|ERADTO $era Array or The DTO containing the parameters for the request.
     * @return array The list of electronic remittance advices.
     * @throws GuzzleException HTTP Request Failure
     */
    public function getList(array|ERADTO $era): array
    {
        if ($era instanceof ERADTO) {
            return $this->client->sendRequest('POST', self::ERA_LIST_ENDPOINT, $era->toArray());
        }
        return $this->client->sendRequest('POST', self::ERA_LIST_ENDPOINT, $era);
    }
}