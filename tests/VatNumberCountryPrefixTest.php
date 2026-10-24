<?php

namespace Tests;

use Mpociot\VatCalculator\Http\CurlClient;
use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatNumberCountryPrefixTest extends TestCase {

	//   Validate supported country prefixes without requiring uppercase user input.
	//   Used by TestCase\runTest.
	public function testCountryPrefixesAreCaseInsensitive(): void {
		// Normalize only the country prefix while retaining number-format validation.
		$calculator=new VatCalculator();
		foreach (['de190098891', 'De 190.098-891', 'nl123456789B01', 'frAB345678901'] as $number) {
			$this->assertTrue($calculator->isValidVatNumberFormat($number), $number);
		}
		foreach (['', 'zz190098891', 'de123', 'nl123456789b01'] as $number) {
			$this->assertFalse($calculator->isValidVatNumberFormat($number), $number);
		}
	}


	//   Send uppercase country codes and unchanged number bodies to VIES.
	//   Used by TestCase\runTest.
	public function testViesReceivesNormalizedCountryPrefixes(): void {
		// Capture SOAP arguments without constructing a remote SOAP client.
		$soap=new class {
			public $arguments;

			//   Capture the service request and return a successful fixture response.
			//   Used by VatCalculator\getVATDetails.
			public function checkVat($arguments) {
				// Preserve the exact request fields for the surrounding assertions.
				$this->arguments=$arguments;

				return (object) ['valid' => true];
			}
		};
		$calculator=new VatCalculator();
		$calculator->setSoapClient($soap);
		$numbers=[
			'de 190.098-891'   => ['DE', '190098891'],
			'nl 123456789 B01' => ['NL', '123456789B01']
		];
		foreach ($numbers as $number => [$country, $body]) {
			$this->assertTrue($calculator->getVATDetails($number)->valid);
			$this->assertSame([
				'countryCode' => $country,
				'vatNumber'   => $body
			], $soap->arguments);
		}
	}


	//   Preserve HMRC routing for a lowercase British country prefix.
	//   Used by TestCase\runTest.
	public function testLowercaseBritishPrefixStillUsesHmrc(): void {
		// Inject both HTTP responses so no credentials or network services are used.
		$client=$this->createMock(CurlClient::class);
		$client->expects($this->once())->method('post')->willReturn('{"access_token":"fixture"}');
		$client->expects($this->once())->method('getWithStatus')
			->with($this->stringContains('/lookup/123456789'), $this->anything())
			->willReturn([
				'statusCode' => 200,
				'headers'    => '',
				'body'       => '{"target":{"vatNumber":"123456789"}}'
			]);
		$calculator=new VatCalculator([
			'hmrc' => [
				'client_id'     => 'fixture-id',
				'client_secret' => 'fixture-secret'
			]
		]);
		$calculator->setupCurlClient($client);
		$this->assertSame(['vatNumber' => '123456789'], $calculator->getVATDetails('gb 123456789'));
	}
}