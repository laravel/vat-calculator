<?php

namespace Tests;

use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorStateResetTest extends TestCase {

	//   Apply an explicit individual-customer flag after a business calculation.
	//   Used by TestCase\runTest.
	public function testExplicitFalseClearsCompanyState(): void {
		// Exercise both calculation directions with a custom fixture rate.
		$totals=[
			'calculate'    => 144.0,
			'calculateNet' => 100.0
		];
		foreach ($totals as $method => $expected) {
			$calculator=new VatCalculator([
				'business_country_code' => 'DE',
				'rules'                 => ['FI' => 0.20]
			]);
			$this->assertSame(120.0, $calculator->$method(120, 'FI', null, true));
			$this->assertSame($expected, $calculator->$method(120, 'FI', null, false));
			$this->assertFalse($calculator->isCompany());
		}
	}


	//   Clear an earlier postal exception and country with explicit empty strings.
	//   Used by TestCase\runTest.
	public function testExplicitEmptyClearsLocationState(): void {
		// Use fixture rates with an existing postcode pattern rather than live tax assumptions.
		$totals=[
			'calculate'    => [132.0, 144.0],
			'calculateNet' => [109.09, 100.0]
		];
		foreach ($totals as $method => [$exceptionTotal, $standardTotal]) {
			$calculator=new VatCalculator([
				'rules' => [
					'ES' => [
						'rate'       => 0.20,
						'exceptions' => ['Canary Islands' => 0.10]
					]
				]
			]);
			$this->assertSame($exceptionTotal, $calculator->$method(120, 'ES', '35000'));
			$this->assertSame($standardTotal, $calculator->$method(120, null, ''));
			$this->assertSame('', $calculator->getPostalCode());

			// Clearing the country removes the previous country-specific fixture rate.
			$this->assertSame(120.0, $calculator->$method(120, ''));
			$this->assertSame('', $calculator->getCountryCode());

			// A zero-valued postal string is still an explicitly supplied location.
			$this->assertSame($standardTotal, $calculator->$method(120, 'ES', '0'));
			$this->assertSame('0', $calculator->getPostalCode());
		}
	}


	//   Preserve configured customer and location state when arguments are omitted or null.
	//   Used by TestCase\runTest.
	public function testNullArgumentsKeepPreviousState(): void {
		// Verify reuse without replacing the state with the optional parameter defaults.
		foreach (['calculate', 'calculateNet'] as $method) {
			$calculator=new VatCalculator([
				'business_country_code' => 'DE',
				'rules'                 => ['FI' => 0.20]
			]);
			$calculator->$method(120, 'FI', '00100', true);
			$this->assertSame(120.0, $calculator->$method(120));
			$this->assertSame(120.0, $calculator->$method(120, null, null, null));
			$this->assertSame('FI', $calculator->getCountryCode());
			$this->assertSame('00100', $calculator->getPostalCode());
			$this->assertTrue($calculator->isCompany());
		}
	}
}