<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number range validator
 *
 */
class NumberRangeValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\NumberRangeValidator';

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));

		$this->assertFalse($this->validator->validate(10.5)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForANumberOutOfRange() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertTrue($this->validator->validate(1000.1)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange() {
		$this->validatorOptions(array('minimum' => 1000, 'maximum' => 0));
		$this->assertFalse($this->validator->validate(100)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForAString() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertTrue($this->validator->validate('not a number')->hasErrors());
	}
}

?>