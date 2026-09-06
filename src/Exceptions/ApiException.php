<?php

namespace Nextvisit\ClaimMD\Exceptions;

use Throwable;

/**
 * Thrown for HTTP failures or top-level Claim.MD API errors.
 */
class ApiException extends ClaimMDException
{
    private int $statusCode;
    private ?array $responseBody;
    private array $apiErrors;

    public function __construct(
        int $statusCode,
        ?array $responseBody = null,
        string $message = '',
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $errors = $responseBody['error'] ?? [];
        $this->apiErrors = is_array($errors) ? (array_is_list($errors) ? $errors : [$errors]) : [];

        if ($message === '') {
            $messages = [];
            foreach ($this->apiErrors as $error) {
                $errorMessage = $error['error_mesg'] ?? $error['error_message'] ?? null;
                if (is_string($errorMessage) && $errorMessage !== '') {
                    $messages[] = $errorMessage;
                }
            }
            $message = $messages === []
                ? "Claim.MD API request failed with status code {$statusCode}"
                : implode('; ', $messages);
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

    public function getApiErrors(): array
    {
        return $this->apiErrors;
    }

    /** @return list<string> */
    public function getApiErrorCodes(): array
    {
        $codes = [];
        foreach ($this->apiErrors as $error) {
            $code = $error['error_code'] ?? null;
            if (is_string($code) || is_int($code)) {
                $codes[] = (string) $code;
            }
        }
        return $codes;
    }
}
