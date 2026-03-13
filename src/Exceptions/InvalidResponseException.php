<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when the API response cannot be decoded as valid JSON.
 */
class InvalidResponseException extends ClaimMDException
{
    private int $statusCode;
    private string $rawBody;

    public function __construct(
        int $statusCode,
        string $rawBody,
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->rawBody = $rawBody;

        parent::__construct(
            "Claim.MD API returned a non-JSON response (HTTP {$statusCode})",
            $statusCode,
            $previous
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }
}
