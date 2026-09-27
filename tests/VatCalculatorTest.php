<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use Mockery as m;
use Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException;
use Mpociot\VatCalculator\Http\CurlClient;
use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_calculate_vat_without_country()
    {
        $config = m::mock(Repository::class);

        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $net = 25.00;

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(25.00, $result);
    }

    public function test_calculate_vat_without_country_and_config()
    {
        $net = 25.00;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(25.00, $result);
    }

    public function test_calculate_vat_with_predefined_rules()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => [
                        'rate' => 0.19,
                        'exceptions' => [],
                    ],
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_vat_with_predefined_rules_without_config()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_vat_with_predefined_rules_overwritten_by_configuration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => 0.50,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(36.00, $result);
        $this->assertEquals(0.50, $vatCalculator->getTaxRate());
        $this->assertEquals(12.00, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_direct_set()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_direct_set_without_configuration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_previous_set()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);

        $result = $vatCalculator->calculate($net);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_and_company()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $postalCode = null;
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, $postalCode, $company);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_and_company_set()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(24.00, $vatCalculator->getNetPrice());
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_calculat_vat_with_country_and_company_both_set()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_get_tax_rate_for_location_with_country()
    {
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForLocation($countryCode);
        $this->assertEquals(0.19, $result);
    }

    public function test_get_tax_rate_for_country()
    {
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForCountry($countryCode);
        $this->assertEquals(0.19, $result);
    }

    public function test_get_tax_rate_for_location_with_country_and_company()
    {
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForLocation($countryCode, null, $company);
        $this->assertEquals(0, $result);
    }

    public function test_get_tax_rate_for_country_and_company()
    {
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForCountry($countryCode, $company);
        $this->assertEquals(0, $result);
    }

    public function test_can_validate_valid_vat_number()
    {
        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $result = new \stdClass;
        $result->valid = true;

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'DE',
                'vatNumber' => '190098891',
            ])
            ->willReturn($result);

        $vatNumber = 'DE 190 098 891';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertTrue($result);
    }

    public function test_can_validate_invalid_vat_number()
    {
        $result = new \stdClass;
        $result->valid = false;

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber' => 'meInvalidNumber',
            ])
            ->willReturn($result);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator;
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function test_validate_vat_number_returns_false_on_soap_failure()
    {
        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber' => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator;
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function test_validate_vat_number_returns_false_on_soap_failure_without_forwarding()
    {
        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber' => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function test_validate_vat_number_throws_exception_on_soap_failure()
    {
        $this->expectException(VATCheckUnavailableException::class);

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber' => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn(['forward_soap_faults' => true]);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $vatCalculator->isValidVATNumber($vatNumber);
    }

    public function test_cannot_validate_vat_number_when_service_is_down()
    {
        $this->expectException(VATCheckUnavailableException::class);

        $result = new \stdClass;
        $result->valid = false;

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator;
        $vatCalculator->setSoapClient(false);
        $vatCalculator->isValidVATNumber($vatNumber);
    }

    public function test_can_validate_valid_ukvat_number()
    {
        $config = [
            'hmrc' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
            ],
        ];

        $configMock = m::mock(Repository::class);
        $configMock->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn($config);

        $clientMock = m::mock(CurlClient::class);

        $tokenUrl = 'https://test-api.service.hmrc.gov.uk/oauth/token';
        $tokenResponseBody = json_encode([
            'access_token' => 'test-access-token-123',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ]);

        $clientMock->shouldReceive('post')
            ->once()
            ->with(
                $tokenUrl,
                ['Content-Type: application/x-www-form-urlencoded'],
                'grant_type=client_credentials&client_id=test-client-id&client_secret=test-client-secret',
                false
            )
            ->andReturn($tokenResponseBody);

        $vatNumber = '123456789';
        $validationUrl = "https://test-api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/$vatNumber";

        $apiResponseBody = json_encode([
            'target' => [
                'vatNumber' => $vatNumber,
                'name' => 'Test Company Ltd',
                'address' => [
                    'line1' => '123 Test Street',
                    'postcode' => 'TE1 5ST',
                    'countryCode' => 'GB',
                ],
            ],
        ]);

        $clientMock->shouldReceive('getWithStatus')
            ->once()
            ->with(
                $validationUrl,
                m::on(function ($headers) {
                    $hasAuthHeader = false;
                    $hasAcceptHeader = false;

                    foreach ($headers as $header) {
                        if ($header === 'Authorization: Bearer test-access-token-123') {
                            $hasAuthHeader = true;
                        }
                        if ($header === 'Accept: application/vnd.hmrc.2.0+json') {
                            $hasAcceptHeader = true;
                        }
                    }

                    return $hasAuthHeader && $hasAcceptHeader;
                })
            )
            ->andReturn([
                'statusCode' => 200,
                'headers' => '',
                'body' => $apiResponseBody,
            ]);

        $vatCalculator = new VatCalculator($configMock);

        $fullVatNumber = 'GB'.$vatNumber;
        $result = $vatCalculator->testing($clientMock)->isValidVATNumber($fullVatNumber);

        $this->assertTrue($result);
    }

    public function test_can_validate_invalid_ukvat_number()
    {
        $config = [
            'hmrc' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
            ],
        ];

        $configMock = m::mock(Repository::class);
        $configMock->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn($config);

        $clientMock = m::mock(CurlClient::class);

        $tokenUrl = 'https://test-api.service.hmrc.gov.uk/oauth/token';
        $tokenResponseBody = json_encode([
            'access_token' => 'test-access-token-123',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ]);

        $clientMock->shouldReceive('post')
            ->once()
            ->with(
                $tokenUrl,
                ['Content-Type: application/x-www-form-urlencoded'],
                'grant_type=client_credentials&client_id=test-client-id&client_secret=test-client-secret',
                false
            )
            ->andReturn($tokenResponseBody);

        $vatNumber = '123456789';
        $validationUrl = "https://test-api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/$vatNumber";

        $apiResponseBody = json_encode([
            'code' => 'NOT_FOUND',
            'message' => 'targetVrn does not match a registered company',
        ]);

        $clientMock->shouldReceive('getWithStatus')
            ->once()
            ->with(
                $validationUrl,
                m::on(function ($headers) {
                    $hasAuthHeader = false;
                    $hasAcceptHeader = false;

                    foreach ($headers as $header) {
                        if ($header === 'Authorization: Bearer test-access-token-123') {
                            $hasAuthHeader = true;
                        }
                        if ($header === 'Accept: application/vnd.hmrc.2.0+json') {
                            $hasAcceptHeader = true;
                        }
                    }

                    return $hasAuthHeader && $hasAcceptHeader;
                })
            )
            ->andReturn([
                'statusCode' => 404,
                'headers' => '',
                'body' => $apiResponseBody,
            ]);

        $vatCalculator = new VatCalculator($configMock);

        $fullVatNumber = 'GB'.$vatNumber;
        $result = $vatCalculator->testing($clientMock)->isValidVATNumber($fullVatNumber);

        $this->assertFalse($result);
    }

    public function test_company_in_business_country_gets_valid_vat_rate()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn(['business_country_code' => 'DE']);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_company_in_business_country_gets_valid_vat_rate_direct_set()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;
        $vatCalculator->setBusinessCountryCode('DE');
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_company_outside_business_country_gets_valid_vat_rate()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;
        $vatCalculator->setBusinessCountryCode('NL');
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());
    }

    public function test_returns_zero_for_invalid_country_code()
    {
        $net = 24.00;
        $countryCode = 'XXX';

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());
    }

    public function test_checks_postal_code_for_vat_exceptions()
    {
        $net = 24.00;
        $vatCalculator = new VatCalculator;
        $postalCode = '27498'; // Heligoland
        $result = $vatCalculator->calculate($net, 'DE', $postalCode, false);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());

        $postalCode = '6691'; // Jungholz
        $result = $vatCalculator->calculate($net, 'AT', $postalCode, false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());

        $postalCode = 'BFPO58'; // Dhekelia
        $result = $vatCalculator->calculate($net, 'GB', $postalCode, false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());

        $postalCode = '9122'; // Madeira
        $result = $vatCalculator->calculate($net, 'PT', $postalCode, false);
        $this->assertEquals(29.28, $result);
        $this->assertEquals(0.22, $vatCalculator->getTaxRate());
        $this->assertEquals(5.28, $vatCalculator->getTaxValue());

        $postalCode = '9500-339'; // Azores
        $result = $vatCalculator->calculate($net, 'PT', $postalCode, false);
        $this->assertEquals(27.84, $result);
        $this->assertEquals(0.16, $vatCalculator->getTaxRate());
        $this->assertEquals(3.84, $vatCalculator->getTaxValue());
    }

    public function test_gb_base_postal_code_must_be_the_whole_code()
    {
        $net = 24.00;
        $vatCalculator = new VatCalculator;

        // A longer code that only ends with the base postcode is not that base.
        $result = $vatCalculator->calculate($net, 'GB', 'XXBF12AT', false);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());

        // A longer code that only starts with the BFPO number is not that number.
        $result = $vatCalculator->calculate($net, 'GB', 'BFPO58EXTRA', false);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());

        // The exact base codes still use the Cyprus rate.
        $result = $vatCalculator->calculate($net, 'GB', 'BFPO58', false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());

        $result = $vatCalculator->calculate($net, 'GB', 'BF12AU', false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
    }

    public function test_postal_codes_without_exceptions_get_standard_rate()
    {
        $net = 24.00;
        $vatCalculator = new VatCalculator;

        // Invalid post code
        $postalCode = 'IGHJ987ERT35';
        $result = $vatCalculator->calculate($net, 'ES', $postalCode, false);
        // Expect standard rate for Spain
        $this->assertEquals(29.04, $result);
        $this->assertEquals(0.21, $vatCalculator->getTaxRate());
        $this->assertEquals(5.04, $vatCalculator->getTaxValue());

        // Valid BE post code
        $postalCode = '2000';
        $result = $vatCalculator->calculate($net, 'BE', $postalCode, false);
        // Expect standard rate for BE
        $this->assertEquals(29.04, $result);
        $this->assertEquals(0.21, $vatCalculator->getTaxRate());
        $this->assertEquals(5.04, $vatCalculator->getTaxValue());
    }

    public function test_postal_codes_without_exceptions_overwritten_by_configuration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => [
                        'rate' => 0.19,
                        'exceptions' => [
                            'Heligoland' => 0.05,
                        ],
                    ],
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $postalCode = '27498'; // Heligoland
        $result = $vatCalculator->calculate($net, $countryCode, $postalCode, false);
        $this->assertEquals(25.20, $result);
        $this->assertEquals(0.05, $vatCalculator->getTaxRate());
        $this->assertEquals(1.20, $vatCalculator->getTaxValue());
    }

    public function test_should_collect_vat()
    {
        $vatCalculator = new VatCalculator;
        $this->assertTrue($vatCalculator->shouldCollectVAT('DE'));
        $this->assertTrue($vatCalculator->shouldCollectVAT('NL'));
        $this->assertFalse($vatCalculator->shouldCollectVAT(''));
        $this->assertFalse($vatCalculator->shouldCollectVAT('XXX'));
    }

    public function test_should_collect_vat_from_config()
    {
        $countryCode = 'TEST';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $this->assertTrue($vatCalculator->shouldCollectVAT($countryCode));
    }

    public function test_calculate_net_price_without_country()
    {
        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $gross = 25.00;

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(25.00, $result);
    }

    public function test_calculate_net_price_without_country_and_config()
    {
        $gross = 25.00;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(25.00, $result);
    }

    public function test_calculate_net_price_with_predefined_rules()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_predefined_rules_without_config()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_predefined_rules_overwritten_by_configuration()
    {
        $gross = 36.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => 0.50,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.50, $vatCalculator->getTaxRate());
        $this->assertEquals(12.00, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_direct_set()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_direct_set_without_configuration()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator;

        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_previous_set()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    'DE' => 0.19,
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);

        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_and_company()
    {
        $gross = 28.56;
        $countryCode = 'DE';
        $postalCode = null;
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode, $postalCode, $company);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_and_company_set()
    {
        $gross = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(24.00, $vatCalculator->getNetPrice());
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_calculate_net_price_with_country_and_company_both_set()
    {
        $gross = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([]);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function test_calculate_high_vat_type()
    {
        $gross = 24.00;
        $countryCode = 'NL';
        $company = false;
        $type = 'high';
        $postalCode = null;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(29.04, $result);
    }

    public function test_calculate_low_vat_type()
    {
        $gross = 24.00;
        $countryCode = 'NL';
        $company = false;
        $type = 'low';
        $postalCode = null;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(26.16, $result);
    }

    public function test_calculate_low_vat_vat_with_predefined_rules_overwritten_by_configuration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => [
                        'rate' => 0.19,
                        'rates' => [
                            'high' => 0.19,
                            'low' => 0.03,
                        ],
                    ],
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, null, null, 'low');
        $this->assertEquals(24.72, $result);
        $this->assertEquals(0.03, $vatCalculator->getTaxRate());
        $this->assertEquals(0.72, $vatCalculator->getTaxValue());
    }

    public function test_calculate_exception_high_vat_with_type_param()
    {
        $gross = 24.00;
        $countryCode = 'FR';
        $company = false;
        $type = 'high';
        $postalCode = 97123;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(26.04, $result);
    }

    public function test_calculate_exception_low_vat_with_type_param()
    {
        $gross = 24.00;
        $countryCode = 'FR';
        $company = false;
        $type = 'low';
        $postalCode = 97123;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(24.5, $result);
    }

    public function test_calculate_exception_default_vat_without_type_param()
    {
        $gross = 24.00;
        $countryCode = 'FR';
        $company = false;
        $postalCode = 97123;

        $vatCalculator = new VatCalculator;
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company);

        $this->assertEquals(26.04, $result);
    }

    public function test_calculate_exception_low_vat_vat_with_predefined_rules_overwritten_by_configuration()
    {
        $net = 24.00;
        $countryCode = 'FR';

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator', [])
            ->andReturn([
                'rules' => [
                    $countryCode => [
                        'rate' => 0.19,
                        'rates' => [
                            'high' => 0.19,
                            'low' => 0.07,
                        ],
                        'exceptions' => [
                            'Guadeloupe' => [
                                'rate' => 0.5,
                                'rates' => [
                                    'high' => 0.085,
                                    'low' => 0.021,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, 97123, false);
        $this->assertEquals(36, $result);
    }

    #[Covers('VatCalculator::isValidVatNumberFormat')]
    public function test_is_valid_vat_number_format()
    {
        $valid = [
            'ATU12345678',
            'BE0123456789',
            'BE1234567891',
            'BG123456789',
            'BG1234567890',
            'CY12345678X',
            'CZ12345678',
            'DE123456789',
            'DE 190 098 891',
            'DK12345678',
            'DK99 99 99 99',
            'EE123456789',
            'EL123456789',
            'ESX12345678',
            'FI12345678',
            'FR12345678901',
            'FRA2345678901',
            'FRAB345678901',
            'FR1B345678901',
            'GB999999973',
            'HU12345678',
            'HR12345678901',
            'IE1234567X',
            'IT12345678901',
            'LT123456789',
            'LU12345678',
            'LV12345678901',
            'MT12345678',
            'NL123456789B12',
            'NL 123456789 B01',
            'PL1234567890',
            'PT123456789',
            'RO123456789',
            'SE123456789012',
            'SI12345678',
            'SK1234567890',
        ];

        $vatCalculator = new VatCalculator;

        foreach ($valid as $format) {
            $this->assertTrue($vatCalculator->isValidVatNumberFormat($format), "{$format} did not pass validation.");
        }

        $invalid = [
            '',
            'ATU1234567',
            'BE012345678',
            'BE123456789',
            'BG1234567',
            'CY1234567X',
            'CZ1234567',
            'DE12345678',
            'DK1234567',
            'EE12345678',
            'EL12345678',
            'ESX1234567',
            'FI1234567',
            'FR1234567890',
            'GB99999997',
            'HU1234567',
            'HR1234567890',
            'IE123456X',
            'IT1234567890',
            'LT12345678',
            'LU1234567',
            'LV1234567890',
            'MT1234567',
            'NL12345678B12',
            'PL123456789',
            'PT12345678',
            'RO1',  // Romania has a really weird VAT format...
            'SE12345678901',
            'SI1234567',
            'SK123456789',

            // valid number but with prefix
            'invalid_prefix_GB999999973',
            'invalid_prefix_IE1234567X',
            'invalid_prefix_ESB1234567C',
            'invalid_prefix_BE0123456789',
            'invalid_prefix_MT12345678',
            'invalid_prefix_LT123456789',

            // valid number but with suffix
            'IE1234567X_invalid_suffix',
            'ESB1234567C_invalid_suffix',
            'BE0123456789_invalid_suffix',
            'MT12345678_invalid_suffix',
            'LT123456789_invalid_suffix',
        ];

        foreach ($invalid as $format) {
            $isValid = $vatCalculator->isValidVatNumberFormat($format);
            $this->assertFalse($isValid, "{$format} passed validation, but shouldn't.");
        }
    }
}
