<?php

use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;
use Nextvisit\ClaimMDWrapper\Requests\ClaimRequest;

describe('ClaimRequest', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(Client::class);
        $this->claimRequest = new ClaimRequest($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('archive', function () {
        it('sends archive request with claim ID', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/archive/', ['claimid' => 'CLAIM123'])
                ->andReturn(['status' => 'archived']);

            $result = $this->claimRequest->archive('CLAIM123');

            expect($result)->toBe(['status' => 'archived']);
        });
    });

    describe('listModifications', function () {
        it('sends request with no filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/modify/', [])
                ->andReturn(['modifications' => []]);

            $result = $this->claimRequest->listModifications();

            expect($result)->toBe(['modifications' => []]);
        });

        it('sends request with modId filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/modify/', ['ModID' => 'MOD123'])
                ->andReturn(['modifications' => ['mod1']]);

            $result = $this->claimRequest->listModifications(modId: 'MOD123');

            expect($result)->toBe(['modifications' => ['mod1']]);
        });

        it('sends request with claimMdId filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/modify/', ['ClaimMD_ID' => 'CMD123'])
                ->andReturn(['modifications' => []]);

            $result = $this->claimRequest->listModifications(claimMdId: 'CMD123');

            expect($result)->toBe(['modifications' => []]);
        });

        it('sends request with field filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/modify/', ['Field' => 'status'])
                ->andReturn(['modifications' => []]);

            $result = $this->claimRequest->listModifications(field: 'status');

            expect($result)->toBe(['modifications' => []]);
        });

        it('sends request with all filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/modify/', ['ModID' => 'MOD123', 'ClaimMD_ID' => 'CMD123', 'Field' => 'status'])
                ->andReturn(['modifications' => []]);

            $result = $this->claimRequest->listModifications('MOD123', 'CMD123', 'status');

            expect($result)->toBe(['modifications' => []]);
        });
    });

    describe('appeal', function () {
        it('sends appeal request with ClaimAppealDTO', function () {
            $dto = new ClaimAppealDTO(
                claimId: 'CLAIM123',
                contactName: 'John Doe',
                contactEmail: 'john@example.com'
            );

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/appeal/', [
                    'claimid' => 'CLAIM123',
                    'contact_name' => 'John Doe',
                    'contact_email' => 'john@example.com',
                ])
                ->andReturn(['status' => 'appealed']);

            $result = $this->claimRequest->appeal($dto);

            expect($result)->toBe(['status' => 'appealed']);
        });

        it('sends appeal request with array', function () {
            $data = [
                'claimid' => 'CLAIM123',
                'contact_name' => 'Jane Doe',
            ];

            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/appeal/', $data)
                ->andReturn(['status' => 'appealed']);

            $result = $this->claimRequest->appeal($data);

            expect($result)->toBe(['status' => 'appealed']);
        });
    });

    describe('notes', function () {
        it('sends request with no filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/notes/', [])
                ->andReturn(['notes' => []]);

            $result = $this->claimRequest->notes();

            expect($result)->toBe(['notes' => []]);
        });

        it('sends request with noteId filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/notes/', ['NoteID' => 'NOTE123'])
                ->andReturn(['notes' => ['note1']]);

            $result = $this->claimRequest->notes(noteId: 'NOTE123');

            expect($result)->toBe(['notes' => ['note1']]);
        });

        it('sends request with claimMdId filter', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/notes/', ['ClaimMD_ID' => 'CMD123'])
                ->andReturn(['notes' => []]);

            $result = $this->claimRequest->notes(claimMdId: 'CMD123');

            expect($result)->toBe(['notes' => []]);
        });

        it('sends request with both filters', function () {
            $this->mockClient
                ->shouldReceive('sendRequest')
                ->once()
                ->with('POST', '/services/notes/', ['ClaimMD_ID' => 'CMD123', 'NoteID' => 'NOTE123'])
                ->andReturn(['notes' => []]);

            $result = $this->claimRequest->notes('NOTE123', 'CMD123');

            expect($result)->toBe(['notes' => []]);
        });
    });
});
