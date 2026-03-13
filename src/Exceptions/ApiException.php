<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when the Claim.MD API returns an HTTP error response.
 */
class ApiException extends ClaimMDException
{
    private int $statusCode;
    private ?array $responseBody;

    public function __construct(
        int $statusCode,
        ?array $responseBody = null,
        string $message = '',
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        if ($message === '') {
            $message = "Claim.MD API request failed with status code {$statusCode}";
        }

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
