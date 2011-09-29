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
 * Testcase for the number validator
 *
 */
class NumberValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\NumberValidator';

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	protected $numberParser;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		parent::setUp();
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');

		$this->mockNumberParser = $this->getMock('TYPO3\FLOW3\I18n\Parser\NumberParser');
		
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$sampleInvalidNumber = 'this is not a number';

		$this->mockNumberParser->expects($this->once())->method('parseDecimalNumber', $sampleInvalidNumber)->will($this->returnValue(FALSE));

		$this->validatorOptions(array('locale' => $this->sampleLocale));
		$this->validator->injectNumberParser($this->mockNumberParser);

		$this->assertEquals(1, count($this->validator->validate($sampleInvalidNumber)->getErrors()));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsFalseForIncorrectValues() {
		$sampleInvalidNumber = 'this is not a number';

		$this->mockNumberParser->expects($this->once())->method('parsePercentNumber', $sampleInvalidNumber)->will($this->returnValue(FALSE));

		$this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT));
		$this->validator->injectNumberParser($this->mockNumberParser);

		$this->assertEquals(1, count($this->validator->validate($sampleInvalidNumber)->getErrors()));
	}
}

?>