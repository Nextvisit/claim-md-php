# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Unofficial PHP SDK for the Claim.MD API — a healthcare/insurance claims processing platform. The library wraps Claim.MD's REST API with typed DTOs, validation, and a thin Guzzle-based HTTP client.

**Namespace:** `Nextvisit\ClaimMD` (PSR-4 autoloaded from `src/`)

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
```

## Architecture

**Client + Config** — `Client` wraps Guzzle, auto-injects `AccountKey` into every request, and returns decoded JSON arrays. `Config` holds the base URI (`https://svc.claim.md/`).

**DTOs** (`src/DTO/`) — Readonly classes with constructor validation. Each DTO:
- Validates required fields and formats in the constructor (throws `InvalidArgumentException`)
- Has `toArray()` that maps camelCase properties to the API's expected field names (varies per endpoint: snake_case, PascalCase, or mixed)
- Has static `fromArray()` factory
- Filters out null values in `toArray()`

**Request classes** (`src/Requests/`) — One per API domain (ERA, Claim, File, Provider, Eligibility, Response, Payer). Each:
- Accepts `Client` via constructor injection
- Defines endpoint URIs as private constants
- Methods accept either a DTO or a raw array
- Returns raw API response arrays

**Key patterns:**
- `ResponseRequest::fetchAllResponses()` uses a Generator for auto-paginated iteration via `last_responseid`
- `FileRequest::upload()` and `EligibilityRequest::checkEligibility270271()` use multipart form uploads (accept PHP `resource` handles)
- DTOs handle field name transformation — the Request classes send whatever `toArray()` returns

## Testing

- **Framework:** Pest 3.0 with Mockery
- **Structure:** `tests/Unit/DTO/` for DTO validation tests, `tests/Unit/Requests/` for request tests with mocked Client
- **Client tests** use Guzzle's `MockHandler` for HTTP-level mocking; Request tests mock the `Client` class directly with Mockery
- **PHP version:** 8.3+ required