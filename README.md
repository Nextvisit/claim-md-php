# ClaimMD PHP Wrapper

![CLAIM.md](https://cdn.prod.website-files.com/6619250355c3f9e1344f80b5/6619305fba1aef8ce5858ae7_claimmd_glow_120.png)

Welcome to the unofficial PHP wrapper for the [CLAIM.MD](https://www.claim.md/) API! 🎉 This library aims to simplify interactions with the official CLAIM.md API, providing a more developer-friendly way to integrate CLAIM.md services into your PHP applications.

## ⚠️ Disclaimer
**Nextvisit Inc. is not affiliated with CLAIM.MD in any way. This package is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose, and noninfringement. Use at your own risk.**

That being said, if you encounter any issues or have suggestions for improvement, feel free to open an issue or contribute to the package. 😊


## 🌟 Features

This library provides a range of features to interact with the CLAIM.MD API:
### [Electronic Remittance Advice (ERA) Management](#electronic-remittance-advice-era)
- [**List Received ERAs**](#get-eras-list): Get a list of all ERAs that have been received.
- [**Get ERA 835**](#get-an-era-835): Get a specific ERA in the 835 format.
- [**Get ERA PDF**](#get-an-era-pdf): Get a specific ERA in the PDF format.
- [**Get ERA PDF**](#get-an-era-pdf): Get a specific ERA in the JSON format.

### [File Management](#file-management)
- [**Upload Files**](#upload-files): Upload batch files to the CLAIM.md service.
- [**Get Upload List**](#get-upload-list): Retrieve a list of uploaded files.

### [Provider Management](#provider-management)
- [**Enroll Providers**](#enroll-providers): Enroll providers using detailed enrollment data.
- [**Fetch Provider Enrollment**](#enroll-providers): If a provider is already enrolled, that information will be received from  the same `enroll()` method.

### [Claim Management](#claim-management)
- [**Claim Appeal**](#claim-appeal): Submit and manage claim appeals.
- [**Fetch Claim Notes**](#fetch-claim-notes): Retrieve notes made on a specific claim.
- [**Archive Claim**](#archive-claim): Archive a Claim.MD claim.
- [**Claim Modifications**](#list-claim-modifications): Retrieve modifications for claims.

### [Response (Claim Status)](#response-claim-status)
##### This refers to a claim status response, not an HTTP or protocol response.
- [**Fetch Responses**](#fetch-response): Retrieve claim statuses (referred to as "responses" in the Claim.MD API) from the Claim MD API.
- [**Fetch All Responses**](#fetch-all-responses): Automatically handle pagination to retrieve all claim statuses.

### [Eligibility](#eligibility)
- [**Realtime Eligibility X12 270/271**](#realtime-x12-270271-eligibility-check): Validate and check the eligibility of X12 270/271 formatted claim. Receiving the response in the format as well.
- [**Realtime Eligibility JSON**](#realtime-parameter-eligibility-check): Validate and check the eligibility of a claim via parameters. Receiving the response in JSON format.

### [Payer](#payers)
- [**Fetch Payers**](#list-payers): Retrieve a list of payers or a specific payer.

### [Data Transfer Objects (DTOs)](#data-transfer-objects-dtos)
#### DTOs can make passing data to and from cleaner in your code. Consider using them over a traditional array.
#### Note: All methods which use a DTO can instead take an array with correct [Claim.MD API](https://api.claim.md) mappings.
- [**ClaimAppealDTO**](#claimappealdto)
- [**ProviderEnrollmentDTO**](#providerenrollmentdto)
- [**EligibilityDTO**](#eligibilitydto)
- [**ERADTO**](#eradto)

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

### Electronic Remittance Advice (ERA)

### Get ERAs List

```php
use Nextvisit\ClaimMDWrapper\Requests\ERARequest;
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

$eraRequest = new ERARequest($client);

// optionally pass an array or ERADTO with at least the ERA ID to get remits since then.
$eraDto = new ERADTO(eraId: 'era-id');
$recentResponse = $eraRequest->getList($eraDto);

$allResponse = $eraRequest->getList();
```

### Get an ERA 835
```php
use Nextvisit\ClaimMDWrapper\Requests\ERARequest;

$eraRequest = new ERARequest($client);
$response = $eraRequest->get835('era-id');
```

### Get an ERA PDF
```php
use Nextvisit\ClaimMDWrapper\Requests\ERARequest;

$eraRequest = new ERARequest($client);

// optional PCN can be used after the era-id. 
$pcnResponse = $eraRequest->getPDF('era-id', 'pcn');

$regularResponse = $eraRequest->getPDF('era-id');
```

### Get an ERA JSON
```php
use Nextvisit\ClaimMDWrapper\Requests\ERARequest;

$eraRequest = new ERARequest($client);
$response = $eraRequest->getJson('era-id');
```

### File Management

#### Upload Files

```php
use Nextvisit\ClaimMDWrapper\Requests\FileRequest;

$fileRequest = new FileRequest($client);
$response = $fileRequest->upload(fopen('path/to/your/file.txt', 'r'));

print_r($response);
```

#### Get Upload List

```php
use Nextvisit\ClaimMDWrapper\Requests\FileRequest;

$fileRequest = new FileRequest($client);
$response = $fileRequest->getUploadList();
```

### Provider Management

#### Enroll Providers

```php
use Nextvisit\ClaimMDWrapper\Requests\ProviderRequest;
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

// If this provider is already enrolled, the response will contain that information.
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
```

### Claim Management

#### Claim Appeal

```php
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;
use Nextvisit\ClaimMDWrapper\Requests\ClaimRequest;

$appealDto = new ClaimAppealDTO(
    claimId: 'claim-id',
    remoteClaimId: 'remote-claim-id',
    contactName: 'Jane Doe',
    contactEmail: 'jane.doe@example.com',
    contactPhone: '+1234567890'
);
$claimRequest = new ClaimRequest($client);
$response = $claimRequest->appeal($appealDto);
```

#### Archive Claim

```php
use Nextvisit\ClaimMDWrapper\Requests\ClaimRequest;

$claimRequest = new ClaimRequest($client);
$response = $claimRequest->archive('claim-id');
```

#### List Claim Modifications

```php
use Nextvisit\ClaimMDWrapper\Requests\ClaimRequest;

$claimRequest = new ClaimRequest($client);

$modId = 'mod-id'; // List modifications that have occurred after modId by specifying.
$claimMdId = 'claimmd-id'; // Get responses for a specific claim.
$field = 'some-field'; // Specify changes to a particular field.
$specifiedResponse = $claimRequest->listModifications($modId, $claimMdId, $field);

// list all modifications to all claims
$allResponse = $claimRequest->listModifications();
```

#### Fetch Claim Notes

```php
use Nextvisit\ClaimMDWrapper\Requests\ClaimRequest;

$claimRequest = new ClaimRequest($client);

// specify notesId to get notes since that note.
$noteId = 'note-id';
// specify claimId to get notes on just that claim.
$claimId = 'claim-id';
$specifiedResponse = $claimRequest->notes($noteId, $claimId);

// list all notes on all claims
$allResponse = $claimRequest->notes();
```

### Response (Claim Status)

#### Fetch Response
```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);

// Get responses since responseId
$responseId = 'response-id';
$recentResponse = $responseRequest->fetchResponses($responseId);
```

#### Fetch All Responses
```php
use Nextvisit\ClaimMDWrapper\Requests\ResponseRequest;

$responseRequest = new ResponseRequest($client);

// optionally pass the claimmd-id to get responses for that id
// Note per the ClaimMD API "Do not use this option for periodic status updates".
foreach ($responseRequest->fetchAllResponses() as $response) {
    // do something with the response
    print_r($response);
}
```

### Eligibility

#### Realtime X12 270/271 Eligibility Check

```php
use Nextvisit\ClaimMDWrapper\Requests\EligibilityRequest;

$eligibilityRequest = new EligibilityRequest($client);

$eligibility270 = fopen('path/to/your/file.270', 'r');

// the response will contain a 270 file
$realtimeResponse = $eligibilityRequest->checkEligibility270271($eligibility270);
```

#### Realtime Parameter Eligibility Check
```php
use Nextvisit\ClaimMDWrapper\Requests\EligibilityRequest;
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

$eligibilityRequest = new EligibilityRequest($client);

// build an EligibilityDTO or pass an existing array
$eligDto = new EligibilityDTO(
    insLastName: 'Doe',
    insFirstName: 'Jane',
    payerId: 'payer-id',
    // 18 or G8 are the only valid codes
    patientRelationship: '18',
    serviceDate: '20220203',
    providerNpi: 'provider-npi', 
    providerTaxId: 'provider-tax-id'
);

// response will be in the response array
$response = $eligibilityRequest->checkEligibilityJSON($eligDto);
```

### Payers

#### List Payers
```php
use Nextvisit\ClaimMDWrapper\Requests\PayerRequest;

$payerRequest = new PayerRequest($client);

// specify a specific payer
$payerId = 'payer-id';
$specifiedPayerResponse = $payerRequest->listPayer(payerId: $payerId);

// specify a general payer name search
$payerName = 'payer-name';
$searchResponse = $payerRequest->listPayer($payerName: $payerName);

// get all payers
$allResponse = $payerRequest->listPayer();
```

### Data Transfer Objects (DTOs)

#### ClaimAppealDTO

```php
use Nextvisit\ClaimMDWrapper\DTO\ClaimAppealDTO;

$claimAppealDto = new ClaimAppealDTO(
    claimId: '12345',
    remoteClaimId: 'remote-claim-123',
    contactName: 'John Doe',
    contactTitle: 'Claims Manager',
    contactEmail: 'john.doe@example.com',
    contactPhone: '+1-555-123-4567',
    contactFax: '+1-555-765-4321',
    contactAddr1: '123 Main St',
    contactAddr2: 'Suite 456',
    contactCity: 'Los Angeles',
    contactState: 'CA',
    contactZip: '90001'
);

// or construct from an array
$data = [
    'claimid'        => '12345',
    'remote_claimid' => 'remote-claim-123',
    'contact_name'   => 'John Doe',
    'contact_title'  => 'Claims Manager',
    'contact_email'  => 'john.doe@example.com',
    'contact_phone'  => '+1-555-123-4567',
    'contact_fax'    => '+1-555-765-4321',
    'contact_addr_1' => '123 Main St',
    'contact_addr_2' => 'Suite 456',
    'contact_city'   => 'Los Angeles',
    'contact_state'  => 'CA',
    'contact_zip'    => '90001',
];

$claimAppealDto = ClaimAppealDTO::fromArray($data);
```

#### ProviderEnrollmentDTO

```php
use Nextvisit\ClaimMDWrapper\DTO\ProviderEnrollmentDTO;

$providerEnrollmentDto = new ProviderEnrollmentDTO(
    payerId: 'payer-123',
    enrollType: 'era',
    provTaxId: '123456789',
    provNpi: '9876543210',
    provNameLast: 'Doe',
    provNameFirst: 'John',
    provNameMiddle: 'M',
    contact: 'Jane Smith',
    contactTitle: 'Billing Manager',
    contactEmail: 'jane.smith@example.com',
    contactPhone: '+1-555-123-4567',
    contactFax: '+1-555-765-4321',
    provId: 'provider-id-123',
    provAddr1: '123 Main St',
    provAddr2: 'Suite 100',
    provCity: 'Los Angeles',
    provState: 'CA',
    provZip: '90001'
);

// or generate from an array
$data = [
    'payerid'        => 'payer-123',
    'enroll_type'    => 'era',
    'prov_taxid'     => '123456789',
    'prov_npi'       => '9876543210',
    'prov_name_l'    => 'Doe',
    'prov_name_f'    => 'John',
    'prov_name_m'    => 'M',
    'contact'        => 'Jane Smith',
    'contact_title'  => 'Billing Manager',
    'contact_email'  => 'jane.smith@example.com',
    'contact_phone'  => '+1-555-123-4567',
    'contact_fax'    => '+1-555-765-4321',
    'prov_id'        => 'provider-id-123',
    'prov_addr_1'    => '123 Main St',
    'prov_addr_2'    => 'Suite 100',
    'prov_city'      => 'Los Angeles',
    'prov_state'     => 'CA',
    'prov_zip'       => '90001',
];

$providerEnrollmentDto = ProviderEnrollmentDTO::fromArray($data);
```

#### EligibilityDTO

```php
use Nextvisit\ClaimMDWrapper\DTO\EligibilityDTO;

$eligibilityDto = new EligibilityDTO(
    insLastName: 'Doe',
    insFirstName: 'John',
    payerId: 'payer-123',
    patientRelationship: '18',
    serviceDate: '20230920',
    providerNpi: '9876543210',
    providerTaxId: '123456789',
    insMiddleName: 'M',
    serviceCode: 'SC123',
    procCode: 'PC456',
    insNumber: 'INS987654',
    insDob: '19800101',
    insSex: 'M',
    patLastName: 'Smith',
    patFirstName: 'Jane',
    patMiddleName: 'K',
    patDob: '20100101',
    patSex: 'F',
    provNameLast: 'Doe',
    provNameFirst: 'Jane',
    provTaxonomy: '207Q00000X',
    provTaxIdType: 'E',
    provAddr1: '123 Main St',
    provCity: 'Los Angeles',
    provState: 'CA',
    provZip: '90001'
);

// or convert an array to DTO
$data = [
    'ins_name_l' => 'Doe',
    'ins_name_f' => 'John',
    'payerid' => 'payer-123',
    'pat_rel' => '18',
    'fdos' => '20230920',
    'prov_npi' => '9876543210',
    'prov_taxid' => '123456789',
    'ins_name_m' => 'M',
    'service_code' => 'SC123',
    'proc_code' => 'PC456',
    'ins_number' => 'INS987654',
    'ins_dob' => '19800101',
    'ins_sex' => 'M',
    'pat_name_l' => 'Smith',
    'pat_name_f' => 'Jane',
    'pat_name_m' => 'K',
    'pat_dob' => '20100101',
    'pat_sex' => 'F',
    'prov_name_l' => 'Doe',
    'prov_name_f' => 'Jane',
    'prov_taxonomy' => '207Q00000X',
    'prov_taxid_type' => 'E',
    'prov_addr_1' => '123 Main St',
    'prov_city' => 'Los Angeles',
    'prov_state' => 'CA',
    'prov_zip' => '90001',
];

$eligibilityDto = EligibilityDTO::fromArray($data);
```

#### ERADTO

```php
use Nextvisit\ClaimMDWrapper\DTO\ERADTO;

$eraDto = new ERADTO(
    checkDate: '09-01-2023',
    receivedDate: 'today',
    receivedAfterDate: '08-01-2023',
    checkNumber: '123456',
    checkAmount: '1000.00',
    payerId: 'payer-123',
    npi: '9876543210',
    taxId: '123456789',
    newOnly: '1',
    eraId: 'ERA12345',
    page: '1'
);

// or convert an array to DTO
$data = [
    'CheckDate' => '09-01-2023',
    'ReceivedDate' => 'today',
    'ReceivedAfterDate' => '08-01-2023',
    'CheckNumber' => '123456',
    'CheckAmount' => '1000.00',
    'PayerID' => 'payer-123',
    'NPI' => '9876543210',
    'TaxID' => '123456789',
    'NewOnly' => '1',
    'ERAID' => 'ERA12345',
    'Page' => '1',
];

$eraDto = ERADTO::fromArray($data);
```

## 🤝 Contributing

Contributions are welcome! If you find any issues or have suggestions for improvements, feel free to open an issue or submit a pull request.

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.
