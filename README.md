# VatCalculator

<a href="https://github.com/laravel/vat-calculator/actions">
    <img src="https://github.com/laravel/vat-calculator/workflows/tests/badge.svg" alt="Tests">
</a>
<a href="https://github.com/laravel/vat-calculator/actions/workflows/coding-standards.yml">
    <img src="https://github.com/laravel/vat-calculator/actions/workflows/coding-standards.yml/badge.svg" alt="Coding Standards" />
</a>
<a href="https://packagist.org/packages/mpociot/vat-calculator">
    <img src="https://img.shields.io/packagist/v/mpociot/vat-calculator" alt="Latest Stable Version">
</a>
<a href="https://packagist.org/packages/mpociot/vat-calculator">
    <img src="https://img.shields.io/packagist/dt/mpociot/vat-calculator" alt="Total Downloads">
</a>

Handle all the hard stuff related to EU MOSS tax/vat regulations, the way it should be. Integrates with **Laravel and Cashier** &mdash; or in a **standalone** PHP application. Originally created by [Marcel Pociot](https://pociot.dev).

```php
// Easy to use!
VatCalculator::calculate(24.00, $countryCode = 'DE');
VatCalculator::calculate(24.00, $countryCode, $postalCode);
VatCalculator::calculate(71.00, 'DE', '41352', $isCompany = true);
VatCalculator::getTaxRateForLocation('NL');

// Check validity of a VAT number
VatCalculator::isValidVATNumber('NL123456789B01');
```

> [!WARNING]  
> This package does not provide any promises for correctly calculated taxes. You are still responsible for making sure that any calculated tax is correct for your use case. If you're uncertain whether a certain tax is correct or not, it's best that you talk to an accountant.

## Requirements

- PHP 7.3 or higher
- (optional) Laravel 6.0 or higher

## Installation

Install the package with composer:

```bash
composer require mpociot/vat-calculator
```

### Standalone

You can also use this package without Laravel. Simply create a new instance of the VatCalculator and use it. All documentation examples use the Laravel Facade code, so make sure not to call the methods as if they were static methods.

```php
use Mpociot\VatCalculator\VatCalculator;

$vatCalculator = new VatCalculator();
$vatCalculator->setBusinessCountryCode('DE');
$grossPrice = $vatCalculator->calculate(49.99, $countryCode = 'LU');
```

## Upgrading

Please refer to [`the upgrade guide`](UPGRADE.md) when upgrading the library.

## Usage

### Calculate the gross price

To calculate the gross price use the `calculate` method with a net price and a country code as parameters.

```php
$grossPrice = VatCalculator::calculate(24.00, 'DE');
```

The third parameter is the postal code of the customer.

As a fourth parameter, you can pass in a boolean indicating whether the customer is a company or a private person. If the customer is a company, which you should check by validating the VAT number, the net price gets returned.

```php
$grossPrice = VatCalculator::calculate(24.00, 'DE', '12345', $isCompany = true);
```

### Receive more information

After calculating the gross price you can extract more information from the VatCalculator.

```php
$grossPrice = VatCalculator::calculate(24.00, 'DE'); // 28.56
$taxRate = VatCalculator::getTaxRate(); // 0.19
$netPrice = VatCalculator::getNetPrice(); // 24.00
$taxValue = VatCalculator::getTaxValue(); // 4.56
```

### Receive all tax rates for a given country

To receive an array with all available tax rates for a given country, use the `getTaxRatesForCountry` method.

```php
VatCalculator::getTaxRatesForCountry('DE'); // ["high" => 0.19, "low" => 0.07]
```

### Validate EU VAT numbers

Prior to validating your customers VAT numbers, you can use the `shouldCollectVAT` method to check if the country code requires you to collect VAT
in the first place.

```php
if (VatCalculator::shouldCollectVAT('DE')) {
    // This country code requires VAT collection...
}
```

To validate your customers VAT numbers, you can use the `isValidVATNumber` method. The VAT number should be in a format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11). The given VAT numbers will be truncated and non-relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VATCheckUnavailableException` will be thrown.

```php
try {
    $validVAT = VatCalculator::isValidVATNumber('NL 123456789 B01');
} catch (VATCheckUnavailableException $e) {
    // The VAT check API is unavailable...
}
```

Alternatively, it is also possible to validate only the format of the VAT Number specified by [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11). This is useful if you do not want to wait for a response from the SOAP API.

```php
// This check will return false because no connection to VIES could be made...
$validVAT = VatCalculator::isValidVATNumber('NL 123456789 B01');

// This check will return true because only the format is checked...
$validVAT = VatCalculator::isValidVatNumberFormat('NL 123456789 B01');
```

### Get EU VAT number details

To get the details of a VAT number, you can use the `getVATDetails` method. The VAT number should be in the format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11). The given VAT numbers will be truncated and non relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VATCheckUnavailableException` will be thrown.

```php
try {
    $vat_details = VatCalculator::getVATDetails('NL 123456789 B01');
    print_r($vat_details);
    /* Outputs
    stdClass Object
    (
        [countryCode] => NL
        [vatNumber] => 123456789B01
        [requestDate] => 2017-04-06+02:00
        [valid] => false
        [name] => Name of the company
        [address] => Address of the company
    )
    */
} catch (VATCheckUnavailableException $e) {
    // The VAT check API is unavailable...
}
```

#### UK VAT Numbers

> Note: Validating UK VAT numbers requires registering your application with the HMRC Developer Hub. Please follow the official [HMRC API Documentation](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-registered-companies-api/2.0) for details on authentication and setup.

UK VAT numbers are formatted a little differently:

```php
try {
    $vat_details = VatCalculator::getVATDetails('GB 553557881');
    print_r($vat_details);
    /* Outputs
    array(3) {
        ["name"]=>
            string(26) "Credite Sberger Donal Inc."
        ["vatNumber"]=>
            string(9) "553557881"
        ["address"]=>
            array(3) {
                ["line1"]=>
                    string(18) "131B Barton Hamlet"
                ["postcode"]=>
                    string(8) "SW97 5CK"
                ["countryCode"]=>
                    string(2) "GB"
            }
    }
    */
} catch (VATCheckUnavailableException $e) {
    // The VAT check API is unavailable...
}
```

> 🔐 Configuration

To use the UK VAT validation feature, you'll need to register your application with HMRC and set the following environment variables in your .env file:

```
HMRC_CLIENT_ID="your-client-id"
HMRC_CLIENT_SECRET="your-client-secret"
```

## Laravel

### Configuration

By default, the VatCalculator has all EU VAT rules predefined, so that it can easily be updated, if it changes for a specific country.

If you need to define other VAT rates, you can do so by publishing the configuration and adding more rules.

> **Warning**  
> Be sure to set your business country code in the configuration file, to get correct VAT calculation when selling to business customers in your own country.

To publish the configuration files, run the `vendor:publish` command.

```bash
php artisan vendor:publish --provider="Mpociot\VatCalculator\VatCalculatorServiceProvider"
```

This will create a `vat_calculator.php` in your config directory.

#### Handling SOAP Faults

If the VIES API returns SOAP faults, these errors will be handled gracefully and `false` will be returned. However, if you explicitly want to be aware of any SOAP faults you may instruct VatCalculator to throw them as a `VATCheckUnavailableException`. The `VATCheckUnavailableException` will then contain the specific message of the SOAP fault.

Set the option to `true` in your config file:

```php
<?php

return [
    'forward_soap_faults' => true,
];
```

You can also set a timeout for the SOAP client. By default, SOAP aborts the request to VIES after 30 seconds. If you do not want to wait that long, you can reduce the timeout, for example to 10 seconds:

```php
<?php

return [
    'soap_timeout' => 10,
];
```

### ValidVatNumber Validation Rule

VatCalculator also ships with a `ValidVatNumber` validation rule for VAT Numbers. You can use this when validating input from a form request or a standalone validator instance:

```php
use Mpociot\VatCalculator\Rules\ValidVatNumber;

$validator = Validator::make(Input::all(), [
    'first_name' => 'required',
    'last_name' => 'required',
    'company_vat' => ['required', new ValidVatNumber],
]);

if ($validator->passes()) {
    // Input is correct...
}
```

> [!WARNING]  
> The validator extension returns `false` when the VAT ID Check SOAP API is unavailable.

### Cashier Stripe Integration

> [!NOTE]  
> At the moment this package is not compatible with Cashier Stripe v13 or higher because it still relies on the old `taxPercentage` method which has been removed from Cashier v13. You can still use it on older Cashier Stripe versions in the meantime.

If you want to use this package in combination with [Laravel Cashier Stripe](https://github.com/laravel/cashier-stripe/) you can let your billable model use the `BillableWithinTheEU` trait. Because this trait overrides the `taxPercentage` method of the `Billable` trait, we have to explicitly tell our model to do so.

```php
use Laravel\Cashier\Billable;
use Mpociot\VatCalculator\Traits\BillableWithinTheEU;
use Laravel\Cashier\Contracts\Billable as BillableContract;

class User extends Model implements BillableContract
{
    use Billable, BillableWithinTheEU {
        BillableWithinTheEU::taxPercentage insteadof Billable;
    }

    protected $dates = ['trial_ends_at', 'subscription_ends_at'];
}
```

By using the `BillableWithinTheEU` trait, your billable model has new methods to set the tax rate for the billable model.

Set everything in one command:

- `setTaxForCountry($countryCode, $company = false)`

Or use the more readable, chainable approach:

- `useTaxFrom($countryCode)` &mdash; Use the given country's tax rate
- `asIndividual()` &mdash; The billable model is not a company (default) 
- `asBusiness()` &mdash; The billable model is a valid company

So in order to set the correct tax percentage prior to subscribing your customer, consider the following workflow:

```php
$user = User::find(1);

// For individuals use:
$user->useTaxFrom('NL');

// For business customers with a valid VAT ID, use:
$user->useTaxFrom('NL')->asBusiness();

$user->subscription('monthly')->create($creditCardToken);
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

VatCalculator is maintained by Laravel. Originally created by [Marcel Pociot](https://pociot.dev).

## License

VatCalculator is open-sourced software licensed under [the MIT license](LICENSE.md).
