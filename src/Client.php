<?php

namespace Nextvisit\ClaimMD;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Nextvisit\ClaimMD\Exceptions\ApiException;
use Nextvisit\ClaimMD\Exceptions\AuthenticationException;
use Nextvisit\ClaimMD\Exceptions\InvalidResponseException;
use Nextvisit\ClaimMD\Exceptions\NotFoundException;
use Nextvisit\ClaimMD\Exceptions\RateLimitException;
use Nextvisit\ClaimMD\Exceptions\ServerException;

/**
 * Class Client
 *
 * This class handles HTTP communication with the Claim.MD API.
 *
 * @package Nextvisit\ClaimMD
 */
class Client
{
    private GuzzleClient $httpClient;
    private string $accountKey;
    private Config $config;

    /**
     * Client constructor.
     *
     * @param string $accountKey The account key for API authentication
     * @param Config $config Configuration object (optional)
     * @param GuzzleClient|null $httpClient HTTP client (optional)
     */
    public function __construct(
        string $accountKey,
        Config $config = new Config(),
        ?GuzzleClient $httpClient = null
    ) {
        $this->config = $config;
        $this->accountKey = $accountKey;
        $this->httpClient = $httpClient ?? $this->createDefaultHttpClient();
    }

    /**
     * Create a default HTTP client with predefined configurations.
     *
     * @return GuzzleClient
     */
    private function createDefaultHttpClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->config->getBaseUri(),
            'headers' => [
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Send an HTTP request to the Claim.MD API.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $uri The API endpoint URI
     * @param array $data Request data
     * @param bool $isMultipart Whether the request contains multipart data
     * @param array $additionalHeaders Additional headers to include in the request
     *
     * @return array The API response as an associative array
     *
     * @throws AuthenticationException If the API returns a 401 response
     * @throws RateLimitException If the API rate limit is exceeded (429)
     * @throws NotFoundException If the requested resource is not found (404)
     * @throws ServerException If the API returns a 5xx response
     * @throws ApiException If the API returns any other non-2xx response
     * @throws InvalidResponseException If the response body is not valid JSON
     * @throws GuzzleException If there's a network-level HTTP request failure
     */
    public function sendRequest(string $method, string $uri, array $data = [], bool $isMultipart = false, array $additionalHeaders = []): array
    {
        $options = $this->prepareRequestOptions($data, $isMultipart, $additionalHeaders);

        try {
            $response = $this->httpClient->request($method, $uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if ($response === null) {
                throw $e;
            }

            $this->handleErrorResponse(
                $response->getStatusCode(),
                $response->getBody()->getContents(),
                $response->getHeaderLine('Retry-After'),
                $e
            );
        }

        $statusCode = $response->getStatusCode();
        $rawBody = $response->getBody()->getContents();

        if ($statusCode >= 400) {
            $this->handleErrorResponse(
                $statusCode,
                $rawBody,
                $response->getHeaderLine('Retry-After')
            );
        }

        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            throw new InvalidResponseException($statusCode, $rawBody);
        }

        return $decoded;
    }

    /**
     * Handle an HTTP error response by throwing the appropriate exception.
     *
     * @throws AuthenticationException
     * @throws RateLimitException
     * @throws NotFoundException
     * @throws ServerException
     * @throws ApiException
     */
    private function handleErrorResponse(
        int $statusCode,
        string $rawBody,
        string $retryAfterHeader = '',
        ?\Throwable $previous = null
    ): never {
        $responseBody = json_decode($rawBody, true);

        match (true) {
            $statusCode === 401 => throw new AuthenticationException($responseBody, $previous),
            $statusCode === 404 => throw new NotFoundException($responseBody, $previous),
            $statusCode === 429 => throw new RateLimitException(
                $responseBody,
                $retryAfterHeader !== '' ? (int) $retryAfterHeader : null,
                $previous
            ),
            $statusCode >= 500 => throw new ServerException($statusCode, $responseBody, $previous),
            default => throw new ApiException($statusCode, $responseBody, '', $previous),
        };
    }

    /**
     * Prepare the request options based on the given parameters.
     *
     * @param array $data Request data
     * @param bool $isMultipart Whether the request contains multipart data
     * @param array $additionalHeaders Additional headers to include in the request
     *
     * @return array The prepared request options
     */
    private function prepareRequestOptions(array $data, bool $isMultipart, array $additionalHeaders): array
    {
        $data['AccountKey'] = $this->accountKey;

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($isMultipart) {
            $options = [
                RequestOptions::MULTIPART => $this->prepareMultipartData($data),
                'headers' => array_merge($headers, ['Content-Type' => 'multipart/form-data'], $additionalHeaders),
            ];
        } else {
            $options = [
                RequestOptions::FORM_PARAMS => $data,
                'headers' => array_merge($headers, ['Content-Type' => 'application/x-www-form-urlencoded'], $additionalHeaders),
            ];
        }

        return $options;
    }

    /**
     * Prepare multipart data for file uploads.
     *
     * @param array $data The data to be prepared for multipart request
     *
     * @return array The prepared multipart data
     */
    private function prepareMultipartData(array $data): array
    {
        $multipart = [];
        foreach ($data as $name => $contents) {
            $multipart[] = ['name' => $name, 'contents' => $contents];
        }
        return $multipart;
    }
}
