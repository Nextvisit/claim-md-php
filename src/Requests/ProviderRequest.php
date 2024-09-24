<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

class ProviderRequest
{
    private const ENROLLMENT_ENDPOINT = '/services/enroll/';

    public function __construct(private readonly Client $client) {}

    /**
     * Enrolls a provider using the provided enrollment data.
     *
     * @param array|ProviderEnrollmentDTO $providerEnrollment The enrollment information for the provider.
     * @return array The result of the enrollment request.
     * @throws GuzzleException HTTP Request Failure
     */
    public function enroll(array|ProviderEnrollmentDTO $providerEnrollment): array
    {
        if ($providerEnrollment instanceof ProviderEnrollmentDTO) {
            return $this->client->sendRequest('POST', self::ENROLLMENT_ENDPOINT, $providerEnrollment->toArray());
        }
        return $this->client->sendRequest('POST', self::ENROLLMENT_ENDPOINT, $providerEnrollment);
    }
}