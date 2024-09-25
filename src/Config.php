<?php

namespace Nextvisit\ClaimMDWrapper;

/**
 * Class Config
 *
 * This class manages configuration settings for the Claim.MD API wrapper.
 */
class Config
{
    /**
     * The base URI for the Claim.MD API.
     *
     * @var string
     */
    private string $baseUri = 'https://svc.claim.md/';

    /**
     * Get the base URI for the Claim.MD API.
     *
     * This method returns the configured base URI for making API requests.
     *
     * @return string The base URI as a string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}