<?php

namespace Nextvisit\ClaimMDWrapper;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Class Client
 *
 * This class handles HTTP communication with the Claim.MD API.
 *
 * @package Nextvisit\ClaimMDWrapper
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
     * @throws GuzzleException If there's an HTTP request failure
     */
    public function sendRequest(string $method, string $uri, array $data = [], bool $isMultipart = false, array $additionalHeaders = []): array
    {
        $options = $this->prepareRequestOptions($data, $isMultipart, $additionalHeaders);

        $response = $this->httpClient->request($method, $uri, $options);

        return json_decode($response->getBody()->getContents(), true);
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