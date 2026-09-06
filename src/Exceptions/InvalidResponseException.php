<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when the API response does not match the expected format.
 */
class InvalidResponseException extends ClaimMDException
{
    private int $statusCode;
    private string $rawBody;

    public function __construct(
        int $statusCode,
        string $rawBody,
        ?Throwable $previous = null,
        string $expectedFormat = 'JSON'
    ) {
        $this->statusCode = $statusCode;
        $this->rawBody = $rawBody;

        parent::__construct(
            "Claim.MD API returned a non-{$expectedFormat} response (HTTP {$statusCode})",
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
