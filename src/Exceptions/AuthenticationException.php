<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when authentication fails (HTTP 401).
 *
 * Typically indicates an invalid or expired AccountKey.
 */
class AuthenticationException extends ApiException
{
    public function __construct(
        ?array $responseBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            401,
            $responseBody,
            'Claim.MD API authentication failed. Check your AccountKey.',
            $previous
        );
    }
}
