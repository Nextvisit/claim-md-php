<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when a requested resource is not found (HTTP 404).
 */
class NotFoundException extends ApiException
{
    public function __construct(
        ?array $responseBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(404, $responseBody, 'Claim.MD API resource not found', $previous);
    }
}
