<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when the Claim.MD API returns a server error (HTTP 5xx).
 */
class ServerException extends ApiException
{
    public function __construct(
        int $statusCode,
        ?array $responseBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $statusCode,
            $responseBody,
            "Claim.MD API server error (HTTP {$statusCode})",
            $previous
        );
    }
}
