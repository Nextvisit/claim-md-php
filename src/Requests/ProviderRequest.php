<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

/**
 * Class ProviderRequest
 *
 * Handles provider-related requests to the Claim.MD service.
 */
class ProviderRequest
{
    /**
     * The endpoint for provider enrollment requests.
     */
    private const ENROLLMENT_ENDPOINT = '/services/enroll/';

    /**
     * ProviderRequest constructor.
     *
     * @param Client $client The HTTP client for making requests
     */
    public function __construct(private readonly Client $client) {}

    /**
     * Enrolls a provider using the provided enrollment data.
     *
     * This method sends a provider enrollment request to the Claim.MD API.
     * It accepts either an array of enrollment data or a ProviderEnrollmentDTO object.
     *
     * @param array|ProviderEnrollmentDTO $providerEnrollment The enrollment information for the provider.
     *        If an array is provided, it should contain the necessary enrollment data.
     *        If a ProviderEnrollmentDTO is provided, it will be converted to an array before sending.
     *
     * @return array The result of the enrollment request as returned by the API.
     *
     * @throws GuzzleException If there's an HTTP request failure during the API call.
     */
    public function enroll(array|ProviderEnrollmentDTO $providerEnrollment): array
    {
        if ($providerEnrollment instanceof ProviderEnrollmentDTO) {
            return $this->client->sendRequest('POST', self::ENROLLMENT_ENDPOINT, $providerEnrollment->toArray());
        }
        return $this->client->sendRequest('POST', self::ENROLLMENT_ENDPOINT, $providerEnrollment);
    }
}