# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Unofficial PHP SDK for the Claim.MD API — a healthcare/insurance claims processing platform. The library wraps Claim.MD's REST API with typed DTOs, validation, and a thin Guzzle-based HTTP client.

**Namespace:** `Nextvisit\ClaimMD` (PSR-4 autoloaded from `src/`)

**API reference:** [Claim.MD v1.19](https://api.claim.md/). All 16 service endpoints have wrappers, with DTOs for webhook payloads.

**Dependencies:** PHP 8.3+, Guzzle `^7.15.5 || ^8.1`, Pest `^4.7.8`, and Mockery `^1.6.15`.
Composer's `config.platform.php` is `8.3.0` so updates select dependencies compatible with the minimum PHP version.

## Commands

```bash
# Install dependencies
composer install

# Run all tests (Pest)
composer test

# Run tests with coverage
composer test:coverage

# Run a single test file
./vendor/bin/pest tests/Unit/Requests/ERARequestTest.php

# Run a specific test by name
./vendor/bin/pest --filter="test name here"

composer validate --strict
composer check-platform-reqs
composer audit
php -l src/Client.php
git diff --check
python3 -B -m unittest discover -s .github/scripts -p 'test_*.py'
python3 .github/scripts/release.py --dry-run
```

## Architecture

**Client + Config** — `Client` wraps Guzzle and injects `AccountKey` into every request. `Config` holds the base URI (`https://svc.claim.md/`).
`sendRequest()` returns decoded JSON arrays. `sendX12Request()` requests JSON error bodies and returns `application/edi-x12` bodies unchanged.

**Errors** — Non-empty top-level `error` elements throw `ApiException`, including HTTP 200 responses.
`getApiErrors()` returns object/list errors as a list. `getApiErrorCodes()` returns string codes separately from `getStatusCode()`.
`getResponseBody()` returns the complete decoded response. Messages accept `error_mesg` and `error_message`.
HTTP 401, 404, 429, and 5xx keep their specific exception classes. Per-claim status messages remain response data.
`InvalidResponseException` stores the raw body when the response format is unexpected.

**DTOs** (`src/DTO/`) — Readonly classes with constructor validation. Each DTO:
- Validates required fields and formats in the constructor (throws `InvalidArgumentException`)
- Has `toArray()` that maps camelCase properties to the API's expected field names (varies per endpoint: snake_case, PascalCase, or mixed)
- Has static `fromArray()` factory
- Filters out null values in `toArray()`

**Request classes** (`src/Requests/`) — One per API domain (ERA, Claim, File, Provider, Eligibility, Response, Payer). Each:
- Accepts `Client` via constructor injection
- Defines endpoint URIs as private constants
- Methods accept either a DTO or a raw array
- Returns API response arrays, except claim downloads, which return X12 strings or a generator of strings

**Key patterns:**
- `ResponseRequest::fetchAllResponses()` uses a Generator for auto-paginated iteration via `last_responseid`
- `FileRequest::upload()` and `EligibilityRequest::checkEligibility270271()` use multipart form uploads (accept PHP `resource` handles). Guzzle generates the content type with its boundary.
- `ClaimRequest::archive()` accepts a string or a non-empty list of strings. Bulk calls enable `repeatFormFields` to send repeated `claimid` keys.
- `ClaimRequest::downloadTransmittedClaims()` maps `transmit_date`, `claim_form`, `bill_npi`, `bill_taxid`, `payerid`, and `pg` to `/services/claimdata/`. Dates must be valid calendar dates; forms are `1500`, `ub`, or `dental`; pages start at zero.
- `downloadAllTransmittedClaims()` yields complete 837 pages and stops on HTTP 200 with API error code `711`. Other errors are thrown.
- `ProviderEnrollmentDTO` accepts an SDK-only `isOrganization` flag in its constructor and `fromArray()`. Without an NPI, organizations require a name and individuals require first and last names. The flag is excluded from `toArray()`.
- DTOs handle field name transformation — the Request classes send whatever `toArray()` returns

## Testing

- Complete code, dependency, and documentation updates before adding or running tests and checks.
- **Framework:** Pest 4 with Mockery
- **Structure:** `tests/Unit/DTO/` for DTO validation tests, `tests/Unit/Requests/` for request tests with mocked Client
- **Client tests** use Guzzle's `MockHandler` for HTTP-level mocking; Request tests mock the `Client` class directly with Mockery
- **PHP version:** 8.3+ required

## Releases

- Pushes to `main` and manual runs on `main` use `.github/workflows/release.yml`. Its release job depends on the reusable Tests workflow.
- `.github/scripts/release.py` uses Python's standard library, Git, and the GitHub CLI. Publishing uses the built-in `GITHUB_TOKEN`.
- Use Conventional Commits. `fix`, `perf`, and `revert` select patch; `feat` selects minor; `!` or a `BREAKING CHANGE:` / `BREAKING-CHANGE:` footer selects major. Other types do not release on their own.
- Squash PR titles must carry the commit type and any breaking-change marker.
- Versions come from stable `vX.Y.Z` Git tags. Composer reads the version from the tag; keep the `version` field out of `composer.json`.
- Automation updates `CHANGELOG.md`, commits it, pushes the commit and tag atomically, and publishes the GitHub release. Generated release commits use `[skip ci]`.
- A changed `main` head skips a stale release run. Retries can finish GitHub publication for a tag already pushed by the same run.
