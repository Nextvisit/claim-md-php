# Unofficial ClaimMD PHP Wrapper

![CLAIM.md](https://cdn.prod.website-files.com/6619250355c3f9e1344f80b5/6619305fba1aef8ce5858ae7_claimmd_glow_120.png)

Welcome to the unofficial PHP wrapper for the [CLAIM.MD](https://www.claim.md/) API! 🎉 This library aims to simplify interactions with the CLAIM.md API, providing a more developer-friendly way to integrate CLAIM.md services into your PHP applications.

## ⚠️ Disclaimer
**Nextvisit Inc. is not affiliated with CLAIM.MD in any way. This package is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose, and noninfringement. Use at your own risk.**

That being said, if you encounter any issues or have suggestions for improvement, feel free to open an issue or contribute to the package. 😊


## 🌟 Features

This library provides a range of features to interact with the CLAIM.MD API:

### File Management
- **Upload Files**: Upload batch files to the CLAIM.md service.
- **Get Upload List**: Retrieve a list of uploaded files.

### Provider Management
- **Enroll Providers**: Enroll providers using detailed enrollment data.
- **Fetch Provider Enrollment**: Retrieve provider enrollment information.

### Claim Management
- **Claim Appeal**: Submit and manage claim appeals.
- **Fetch Claim Responses**: Retrieve responses for specific claims.
- **Fetch All Claim Responses**: Automatically handle pagination to fetch all claim responses.

### Eligibility
- **Check Eligibility**: Validate and check the eligibility of claims.

### Response Handling
- **Fetch Responses**: Retrieve responses from the API.
- **Fetch All Responses**: Automatically handle pagination to fetch all responses.

### Data Transfer Objects (DTOs)
- **ClaimAppealDTO**: Manage claim appeal data.
- **ProviderEnrollmentDTO**: Manage provider enrollment data.
- **EligibilityDTO**: Manage eligibility data.
- **ERADTO**: Manage Electronic Remittance Advice (ERA) data.

### Utility Features
- **Configuration Handling**: Easily configure the client with account keys and other settings.
- **Validation**: Built-in validation for fields like dates, emails, phone numbers, state codes, etc.

## 📦 Installation

You can install the package via Composer:

```bash
composer require nextvisit/claim-md-php
```

## 🛠️ Usage

### Configuration

First, configure the `Client` with your account key:

```php
use Nextvisit\ClaimMDWrapper\Client;
use Nextvisit\ClaimMDWrapper\Config;

$accountKey = 'your-account-key'; // Never hardcode your keys!
$config = new Config();

$client = new Client($accountKey, $config);
```

### File Management

#### Upload Files

To upload a file:

```php
use Nextvisit\ClaimMDWrapper\Requests\FileRequest;

$fileRequest = new FileRequest($client);
$response = $fileRequest->upload(fopen('path/to/your/file.txt', 'r'));

print_r($response);
```

#### Get Upload List

To retrieve a list of uploaded files:

```php
use Nextvisit\ClaimMDWrapper\Requests\FileRequest;

$fileRequest = new FileRequest($client);
$response = $fileRequest->getUploadList();

print_r($response);
```

### Provider Management

#### Enroll Providers

To enroll a provider:

```php
use Nextvisit\ClaimMDWrapper\Requests\ProviderRequest;
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

$providerEnrollment = new ProviderEnrollmentDTO(
    payerId: 'payer-id',
    enrollType: 'era',
    provTaxId: 'provider-tax-id',
    provNpi: 'provider-npi',
    provNameLast: 'Doe',
    provNameFirst: 'John',
    contactEmail: 'john.doe@example.com'
);

$providerRequest = new ProviderRequest($client);
$response = $providerRequest->enroll($providerEnrollment);

print_r($response);
```

### Claim Management

#### Claim Appeal

To create a claim appeal:

```php
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;

$claimAppeal = new ClaimAppealDTO(
    claimId: 'claim-id',
    remoteClaimId: 'remote-claim-id',
    contactName: 'Jane Doe',
    contactEmail: 'jane.doe@example.com',
    contactPhone: '+1234567890'
);

print_r($claimAppeal->toArray());
```

#### Fetch Claim Responses

To fetch responses for a specific claim:

```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);
$response = $responseRequest->fetchResponses('response-id', 'claim-id');

print_r($response);
```

#### Fetch All Claim Responses

To fetch all responses, handling pagination automatically:

```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);

foreach ($responseRequest->fetchAllResponses('claim-id') as $response) {
    print_r($response);
}
```

### Eligibility

#### Check Eligibility

To check the eligibility of a claim:

```php
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

$eligibility = new EligibilityDTO(
    insLastName: 'Doe',
    insFirstName: 'John',
    payerId: 'payer-id',
    patientRelationship: '18',
    serviceDate: '20230101',
    providerNpi: 'provider-npi',
    providerTaxId: 'provider-tax-id'
);

print_r($eligibility->toArray());
```

### Response Handling

#### Fetch Responses

To fetch responses from the API:

```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);
$response = $responseRequest->fetchResponses('0');

print_r($response);
```

#### Fetch All Responses

To fetch all responses, handling pagination automatically:

```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);

foreach ($responseRequest->fetchAllResponses() as $response) {
    print_r($response);
}
```

### Data Transfer Objects (DTOs)

#### ClaimAppealDTO

To manage claim appeal data:

```php
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;

$claimAppeal = new ClaimAppealDTO(
    claimId: 'claim-id',
    remoteClaimId: 'remote-claim-id',
    contactName: 'Jane Doe',
    contactEmail: 'jane.doe@example.com',
    contactPhone: '+1234567890'
);

print_r($claimAppeal->toArray());
```

#### ProviderEnrollmentDTO

To manage provider enrollment data:

```php
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

$providerEnrollment = new ProviderEnrollmentDTO(
    payerId: 'payer-id',
    enrollType: 'era',
    provTaxId: 'provider-tax-id',
    provNpi: 'provider-npi',
    provNameLast: 'Doe',
    provNameFirst: 'John',
    contactEmail: 'john.doe@example.com'
);

print_r($providerEnrollment->toArray());
```

#### EligibilityDTO

To manage eligibility data:

```php
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

$eligibility = new EligibilityDTO(
    insLastName: 'Doe',
    insFirstName: 'John',
    payerId: 'payer-id',
    patientRelationship: '18',
    serviceDate: '20230101',
    providerNpi: 'provider-npi',
    providerTaxId: 'provider-tax-id'
);

print_r($eligibility->toArray());
```

#### ERADTO

To manage Electronic Remittance Advice (ERA) data:

```php
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

$era = new ERADTO(
    checkDate: '12-31-2022',
    receivedDate: 'today',
    checkNumber: '123456',
    checkAmount: '100.00',
    payerId: 'payer-id',
    npi: 'provider-npi',
    taxId: 'provider-tax-id',
    newOnly: '1',
    eraId: 'era-id',
    page: '1'
);

print_r($era->toArray());
```

## 🤝 Contributing

Contributions are welcome! If you find any issues or have suggestions for improvements, feel free to open an issue or submit a pull request.

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.