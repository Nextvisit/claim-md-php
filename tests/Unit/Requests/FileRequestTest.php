<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\Requests\FileRequest;

describe('FileRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->fileRequest = new FileRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('getUploadList', function () {
        it('sends request with no parameters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/uploadlist', [])
                ->andReturn(['uploads' => []]);

            $result = $this->fileRequest->getUploadList();

            expect($result)->toBe(['uploads' => []]);
        });

        it('sends request with page parameter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/uploadlist', ['Page' => 2])
                ->andReturn(['uploads' => [], 'page' => 2]);

            $result = $this->fileRequest->getUploadList(page: 2);

            expect($result)->toBe(['uploads' => [], 'page' => 2]);
        });

        it('sends request with uploadDate parameter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/uploadlist', ['UploadDate' => '2024-01-15'])
                ->andReturn(['uploads' => []]);

            $result = $this->fileRequest->getUploadList(uploadDate: '2024-01-15');

            expect($result)->toBe(['uploads' => []]);
        });

        it('sends request with both parameters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/uploadlist', ['Page' => 1, 'UploadDate' => '2024-01-15'])
                ->andReturn(['uploads' => []]);

            $result = $this->fileRequest->getUploadList(1, '2024-01-15');

            expect($result)->toBe(['uploads' => []]);
        });

        it('throws exception for invalid date format', function () {
            $this->fileRequest->getUploadList(uploadDate: '01-15-2024');
        })->throws(InvalidArgumentException::class, 'Upload date must be in the format yyyy-mm-dd');

        it('throws exception for invalid date format with slashes', function () {
            $this->fileRequest->getUploadList(uploadDate: '2024/01/15');
        })->throws(InvalidArgumentException::class, 'Upload date must be in the format yyyy-mm-dd');
    });

    describe('upload', function () {
        it('throws exception when file is not a resource', function () {
            $this->fileRequest->upload('not a resource');
        })->throws(InvalidArgumentException::class, 'Invalid file provided. Must be a resource.');

        it('throws exception for array input', function () {
            $this->fileRequest->upload(['data']);
        })->throws(InvalidArgumentException::class, 'Invalid file provided. Must be a resource.');

        it('throws exception for null input', function () {
            $this->fileRequest->upload(null);
        })->throws(InvalidArgumentException::class, 'Invalid file provided. Must be a resource.');
    });
});
