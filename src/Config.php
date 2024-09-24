<?php

namespace Nextvisit\ClaimMDWrapper;

class Config
{
    private string $baseUri = 'https://svc.claim.md/';

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}