<?php

namespace Nextvisit\ClaimMDWrapper\Requests;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Nextvisit\ClaimMDWrapper\Client;
use Psr\Http\Message\StreamInterface;

/**
 * Class FileRequest
 *
 * Handles file-related requests to the Claim.MD service.
 */
class FileRequest
{
    private const UPLOAD_ENDPOINT = '/services/upload';
    private const UPLOAD_LIST_ENDPOINT = '/services/uploadlist';

    /**
     * FileRequest constructor.
     *
     * @param Client $client The HTTP client for making requests
     */
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Retrieve a list of uploaded files from the Claim.MD service
     *
     * @param int|null $page The page number for paginated results (optional)
     * @param string|null $uploadDate The upload date filter in format yyyy-mm-dd (optional)
     * @return array The API response
     * @throws InvalidArgumentException If upload date is not in the format yyyy-mm-dd
     * @throws GuzzleException HTTP Request Failure
     */
    public function getUploadList(?int $page = null, ?string $uploadDate = null): array
    {
        $data = [];

        if ($page !== null) {
            $data['Page'] = $page;
        }

        if ($uploadDate !== null) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $uploadDate)) {
                throw new InvalidArgumentException('Upload date must be in the format yyyy-mm-dd');
            }
            $data['UploadDate'] = $uploadDate;
        }

        return $this->client->sendRequest('POST', self::UPLOAD_LIST_ENDPOINT, $data);
    }

    /**
     * Upload a batch file to the Claim.MD service
     *
     * @param resource $file The file to upload (must be a resource)
     * @param string|null $filename The name of the file (optional)
     * @return array The API response
     * @throws InvalidArgumentException If file is not a valid resource.
     * @throws GuzzleException HTTP Request Failure
     */
    public function upload($file, ?string $filename = null): array
    {
        $data = [
            'File' => $this->prepareFile($file),
        ];

        if ($filename !== null) {
            $data['Filename'] = $filename;
        }

        return $this->client->sendRequest('POST', self::UPLOAD_ENDPOINT, $data, true);
    }

    /**
     * Prepare the file for upload by converting it to a StreamInterface
     *
     * @param resource $file The file resource to prepare
     * @return StreamInterface The prepared file as a StreamInterface
     * @throws InvalidArgumentException If file is not a valid resource.
     */
    private function prepareFile($file): StreamInterface
    {
        if (is_resource($file)) {
            return Utils::streamFor($file);
        }

        throw new InvalidArgumentException('Invalid file provided. Must be a resource.');
    }
}