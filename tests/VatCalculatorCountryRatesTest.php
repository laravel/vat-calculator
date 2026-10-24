<?php

namespace Tests;

use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorCountryRatesTest extends TestCase {

	//   Return configured named rates for built-in and custom countries regardless of prefix case.
	//   Used by TestCase\runTest.
	public function testConfiguredNamedRatesOverrideBuiltInRates(): void {
		// Define independent fixture rates for a built-in country and a custom country.
		$rates=[
			'high' => 0.20,
			'low'  => 0.05
		];
		$calculator=new VatCalculator([
			'rules' => [
				'FI' => [
					'rate'  => 0.20,
					'rates' => $rates
				],
				'XX' => [
					'rate'  => 0.20,
					'rates' => $rates
				]
			]
		]);
		foreach (['FI', 'fi', 'Fi', 'XX', 'xx'] as $country) {
			$this->assertSame($rates, $calculator->getTaxRatesForCountry($country));
			foreach ($rates as $type => $rate) {
				$this->assertSame($rate, $calculator->getTaxRateForCountry($country, false, $type));
			}
		}
	}


	//   Keep scalar overrides limited to the standard rate as in the existing lookup API.
	//   Used by TestCase\runTest.
	public function testScalarOverridesPreserveBuiltInNamedRates(): void {
		// Compare against the built-in table without hard-coding current legal rates.
		$defaultRates=(new VatCalculator())->getTaxRatesForCountry('DE');
		$calculator=new VatCalculator([
			'rules' => [
				'DE' => 0.50,
				'XX' => 0.50
			]
		]);
		$this->assertSame($defaultRates, $calculator->getTaxRatesForCountry('de'));
		$this->assertSame(0.50, $calculator->getTaxRateForCountry('DE'));
		$this->assertSame([], $calculator->getTaxRatesForCountry('xx'));
		$this->assertSame(0.50, $calculator->getTaxRateForCountry('XX'));
	}


	//   Return an empty array when the effective country rule has no named rates.
	//   Used by TestCase\runTest.
	public function testCountriesWithoutNamedRatesReturnAnEmptyArray(): void {
		// An array override replaces the built-in rule just as it does for individual lookups.
		$calculator=new VatCalculator([
			'rules' => ['FI' => ['rate' => 0.20]]
		]);
		$this->assertSame([], $calculator->getTaxRatesForCountry('FI'));
		$this->assertSame([], $calculator->getTaxRatesForCountry('ZZ'));
	}
}