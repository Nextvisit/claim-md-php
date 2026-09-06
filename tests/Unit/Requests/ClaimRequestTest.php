<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nextvisit\ClaimMD\Client;
use Nextvisit\ClaimMD\DTO\ClaimAppealDTO;
use Nextvisit\ClaimMD\Exceptions\ApiException;
use Nextvisit\ClaimMD\Requests\ClaimRequest;

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

        it('encodes claim IDs as repeated form keys', function (string|array $claimIds, string $encoded) {
            $container = [];
            $mock = new MockHandler([new Response(200, [], '{"status":"archived"}')]);
            $stack = HandlerStack::create($mock);
            $stack->push(Middleware::history($container));
            $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => $stack]));

            expect((new ClaimRequest($client))->archive($claimIds))->toBe(['status' => 'archived']);
            $request = $container[0]['request'];
            expect($request->getUri()->getPath())->toBe('/services/archive/');
            expect($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded');
            expect((string) $request->getBody())->toBe($encoded . '&AccountKey=test-key');
        })->with([
            'single claim' => ['A&1', 'claimid=A%261'],
            'multiple claims' => [['A&1', 'B 2'], 'claimid=A%261&claimid=B+2'],
        ]);

        it('rejects invalid claim ID lists', function (array $claimIds) {
            $this->claimRequest->archive($claimIds);
        })->with([
            'empty' => [[]],
            'empty ID' => [['']],
            'non-string ID' => [[123]],
            'named keys' => [['first' => '123']],
        ])->throws(InvalidArgumentException::class);
    });

    describe('downloadTransmittedClaims', function () {
        it('downloads the first page for each claim form', function (string $claimForm) {
            $this->mockClient->shouldReceive('sendX12Request')->once()
                ->with('POST', '/services/claimdata/', [
                    'transmit_date' => '2024-02-29',
                    'claim_form' => $claimForm,
                    'pg' => 0,
                ])
                ->andReturn('ISA*synthetic-837~');

            expect($this->claimRequest->downloadTransmittedClaims('2024-02-29', $claimForm))->toBe('ISA*synthetic-837~');
        })->with(['1500', 'ub', 'dental']);

        it('maps all download filters and an explicit page', function () {
            $this->mockClient->shouldReceive('sendX12Request')->once()
                ->with('POST', '/services/claimdata/', [
                    'transmit_date' => '2026-08-19',
                    'claim_form' => 'ub',
                    'bill_npi' => '1234567890',
                    'bill_taxid' => '123456789',
                    'payerid' => 'PAYER123',
                    'pg' => 2,
                ])
                ->andReturn('ISA*synthetic-837I~');

            expect($this->claimRequest->downloadTransmittedClaims(
                '2026-08-19', 'ub', '1234567890', '123456789', 'PAYER123', 2
            ))->toBe('ISA*synthetic-837I~');
        });

        it('rejects invalid download parameters', function (string $date, string $form, int $page) {
            $this->claimRequest->downloadTransmittedClaims($date, $form, page: $page);
        })->with([
            ['08/19/2026', '1500', 0],
            ['2026-02-29', '1500', 0],
            ['2026-08-19', '500', 0],
            ['2026-08-19', '1500', -1],
        ])->throws(InvalidArgumentException::class);

        it('throws the no-claims error for a single page', function () {
            $error = new ApiException(200, ['error' => ['error_code' => '711', 'error_mesg' => 'No claims found.']]);
            $this->mockClient->shouldReceive('sendX12Request')->once()->andThrow($error);

            $this->claimRequest->downloadTransmittedClaims('2026-08-19', '1500');
        })->throws(ApiException::class, 'No claims found.');
    });

    describe('downloadAllTransmittedClaims', function () {
        it('yields each X12 page and stops on code 711', function () {
            $pages = ["ISA*page-zero~\r\n", "ISA*page-one~\r\n"];
            $container = [];
            $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'application/edi-x12'], $pages[0]),
                new Response(200, ['Content-Type' => 'application/edi-x12'], $pages[1]),
                new Response(200, ['Content-Type' => 'application/json'], '{"error":{"error_code":"711","error_mesg":"No claims found."}}'),
            ]);
            $stack = HandlerStack::create($mock);
            $stack->push(Middleware::history($container));
            $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => $stack]));
            $request = new ClaimRequest($client);
            $generator = $request->downloadAllTransmittedClaims('2026-08-19', 'dental', '1234567890', '123456789', 'PAYER123');

            expect($container)->toBe([]);
            expect(iterator_to_array($generator))->toBe($pages);
            expect($container)->toHaveCount(3);
            foreach ($container as $page => $transaction) {
                parse_str((string) $transaction['request']->getBody(), $data);
                expect($data)->toBe([
                    'transmit_date' => '2026-08-19',
                    'claim_form' => 'dental',
                    'bill_npi' => '1234567890',
                    'bill_taxid' => '123456789',
                    'payerid' => 'PAYER123',
                    'pg' => (string) $page,
                    'AccountKey' => 'test-key',
                ]);
            }
        });

        it('yields no pages when the first response has code 711', function (array $errors) {
            $mock = new MockHandler([new Response(200, [], json_encode(['error' => $errors]))]);
            $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

            expect(iterator_to_array((new ClaimRequest($client))->downloadAllTransmittedClaims('2026-08-19', '1500')))->toBe([]);
        })->with([
            'object' => [['error_code' => '711', 'error_mesg' => 'No claims found.']],
            'list' => [[['error_code' => 711, 'error_message' => 'No claims found.']]],
        ]);

        it('throws other API and HTTP errors', function (int $status, array $errors) {
            $body = ['error' => $errors];
            $mock = new MockHandler([new Response($status, [], json_encode($body))]);
            $client = new Client('test-key', httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

            try {
                iterator_to_array((new ClaimRequest($client))->downloadAllTransmittedClaims('2026-08-19', '1500'));
                $this->fail('Expected an API exception');
            } catch (ApiException $e) {
                expect($e->getStatusCode())->toBe($status);
                expect($e->getResponseBody())->toBe($body);
            }
        })->with([
            'invalid date' => [200, ['error_code' => '710', 'error_mesg' => 'Invalid transmit_date value.']],
            'invalid form' => [200, ['error_code' => '401', 'error_mesg' => 'Invalid claim_form value.']],
            'mixed errors' => [200, [['error_code' => '711'], ['error_code' => '20']]],
            'HTTP error with 711' => [500, ['error_code' => '711']],
        ]);
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
