<?php

namespace Nextvisit\ClaimMDWrapper;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class Client
{
    private GuzzleClient $httpClient;
    private string $accountKey;
    private Config $config;

    public function __construct(
        string $accountKey,
        Config $config = new Config(),
        ?GuzzleClient  $httpClient = null
    ) {
        $this->config = $config;
        $this->accountKey = $accountKey;
        $this->httpClient = $httpClient ?? $this->createDefaultHttpClient();
    }

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
     * @throws GuzzleException
     */
    public function sendRequest(string $method, string $uri, array $data = [], bool $isMultipart = false, array $additionalHeaders = []): array
    {
        $options = $this->prepareRequestOptions($data, $isMultipart, $additionalHeaders);

        $response = $this->httpClient->request($method, $uri, $options);

        return json_decode($response->getBody()->getContents(), true);
    }

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

    private function prepareMultipartData(array $data): array
    {
        $multipart = [];
        foreach ($data as $name => $contents) {
            $multipart[] = ['name' => $name, 'contents' => $contents];
        }
        return $multipart;
    }
}