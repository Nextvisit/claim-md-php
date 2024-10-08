<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

/**
 * Class ERARequest
 *
 * This class handles requests related to Electronic Remittance Advice (ERA).
 */
class ERARequest
{
    private const ERA_LIST_ENDPOINT = '/services/eralist/';
    private const ERA_835_ENDPOINT = '/services/era835/';
    private const ERA_PDF_ENDPOINT = '/services/erapdf/';
    private const ERA_JSON_ENDPOINT = '/services/eradata/';

    /**
     * ERARequest constructor.
     *
     * @param Client $client The client used to send requests.
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Retrieves the JSON representation of an electronic remittance advice.
     *
     * @param string $eraId The ID of the electronic remittance advice.
     * @return array The JSON representation of the electronic remittance advice.
     * @throws GuzzleException If an HTTP request error occurs.
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
     * @throws GuzzleException If an HTTP request error occurs.
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
     * @throws GuzzleException If an HTTP request error occurs.
     */
    public function get835(string $eraId): array
    {
        return $this->client->sendRequest('POST', self::ERA_835_ENDPOINT, ['eraid' => $eraId]);
    }

    /**
     * Retrieves a list of electronic remittance advices.
     *
     * @param array|ERADTO|null $era Optional array or The DTO containing the parameters for the request.
     * @return array The list of electronic remittance advices.
     * @throws GuzzleException If an HTTP request error occurs.
     */
    public function getList(array|ERADTO|null $era = []): array
    {
        if ($era === null || $era === []) {
            return $this->client->sendRequest('POST', self::ERA_LIST_ENDPOINT);
        }
        if ($era instanceof ERADTO) {
            return $this->client->sendRequest('POST', self::ERA_LIST_ENDPOINT, $era->toArray());
        }
        return $this->client->sendRequest('POST', self::ERA_LIST_ENDPOINT, $era);
    }
}