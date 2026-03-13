<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown when the API rate limit is exceeded (HTTP 429).
 *
 * The Claim.MD API limits requests to 100 per minute.
 */
class RateLimitException extends ApiException
{
    private ?int $retryAfter;

    public function __construct(
        ?array $responseBody = null,
        ?int $retryAfter = null,
        ?Throwable $previous = null
    ) {
        $this->retryAfter = $retryAfter;

        $message = 'Claim.MD API rate limit exceeded (max 100 requests per minute)';
        if ($retryAfter !== null) {
            $message .= ". Retry after {$retryAfter} seconds";
        }

        parent::__construct(429, $responseBody, $message, $previous);
    }

    /**
     * Seconds to wait before retrying, if provided by the API.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
