<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

/**
 * Class EligibilityRequest
 *
 * Handles eligibility-related requests to the ClaimMD API.
 */
class EligibilityRequest
{
    /**
     * Endpoint for eligibility checks using 270/271 files.
     */
    private const ELIGIBILITY_ENDPOINT = '/services/elig/';

    /**
     * Endpoint for eligibility checks using JSON data.
     */
    private const ELIGIBILITY_DATA_ENDPOINT = '/services/eligdata/';

    /**
     * EligibilityRequest constructor.
     *
     * @param Client $client The API client instance.
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Check eligibility by submitting eligibility data in JSON format.
     *
     * @param array|EligibilityDTO $eligibility Array or The eligibility data transfer object
     * @return array The API response
     * @throws GuzzleException If an HTTP Request fails
     */
    public function checkEligibilityJSON(array|EligibilityDTO $eligibility): array
    {
        if ($eligibility instanceof EligibilityDTO) {
            return $this->client->sendRequest('POST', self::ELIGIBILITY_DATA_ENDPOINT, $eligibility->toArray());
        }
        return $this->client->sendRequest('POST', self::ELIGIBILITY_DATA_ENDPOINT, $eligibility);
    }

    /**
     * Check eligibility by submitting a 270 file.
     *
     * @param resource $file The 270 file resource
     * @return array The API response
     * @throws InvalidArgumentException If the file is not a valid resource
     * @throws GuzzleException If an HTTP Request fails
     */
    public function checkEligibility270271(mixed $file): array
    {
        if (!is_resource($file)) {
            throw new InvalidArgumentException('File must be a valid resource');
        }

        $data = [
            'File' => Utils::streamFor($file)
        ];

        return $this->client->sendRequest('POST', self::ELIGIBILITY_ENDPOINT, $data, true);
    }
}