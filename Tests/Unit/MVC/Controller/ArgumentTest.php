<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Controller Argument
 *
 * @covers \TYPO3\FLOW3\MVC\Controller\Argument
 */
class ArgumentTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\Argument
	 */
	protected $simpleValueArgument;

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\Argument
	 */
	protected $objectArgument;

	protected $mockPropertyMapper;
	protected $mockConfigurationBuilder;
	protected $mockConfiguration;

	/**
	 */
	public function setUp() {
		$this->simpleValueArgument = new \TYPO3\FLOW3\MVC\Controller\Argument('someName', 'string');
		$this->objectArgument = new \TYPO3\FLOW3\MVC\Controller\Argument('someName', 'DateTime');

		$this->mockPropertyMapper = $this->getMock('TYPO3\FLOW3\Property\PropertyMapper');
		$this->simpleValueArgument->injectPropertyMapper($this->mockPropertyMapper);
		$this->objectArgument->injectPropertyMapper($this->mockPropertyMapper);

		$this->mockConfigurationBuilder = $this->getMock('TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder');
		$this->mockConfiguration = $this->getMock('TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface');
		$this->mockConfigurationBuilder->expects($this->any())->method('build')->with('TYPO3\FLOW3\MVC\Controller\MvcPropertyMappingConfiguration')->will($this->returnValue($this->mockConfiguration));

		$this->simpleValueArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);
		$this->objectArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);

		$this->simpleValueArgument->initializeObject();
		$this->objectArgument->initializeObject();
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new \TYPO3\FLOW3\MVC\Controller\Argument('', 'Text');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \TYPO3\FLOW3\MVC\Controller\Argument(new \ArrayObject(), 'Text');
	}

	/**
	 * @test
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$this->assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
		$this->assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
	}

	/**
	 * @test
	 */
	public function setShortNameProvidesFluentInterface() {
		$returnedArgument = $this->simpleValueArgument->setShortName('x');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	public function invalidShortNames() {
		return array(
			array(''),
			array('as'),
			array(5)
		);
	}
	/**
	 * @test
	 * @dataProvider invalidShortNames
	 * @expectedException \InvalidArgumentException
	 */
	public function shortNameShouldThrowExceptionIfInvalid($invalidShortName) {
		$this->simpleValueArgument->setShortName($invalidShortName);
	}

	/**
	 * @test
	 */
	public function shortNameCanBeRetrievedAgain() {
		$this->simpleValueArgument->setShortName('x');
		$this->assertEquals('x', $this->simpleValueArgument->getShortName());
	}

	/**
	 * @test
	 */
	public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState() {
		$returnedArgument = $this->simpleValueArgument->setRequired(TRUE);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertTrue($this->simpleValueArgument->isRequired());
	}

	/**
	 * @test
	 */
	public function setShortHelpMessageShouldProvideFluentInterfaceAndReallySetShortHelpMessage() {
		$returnedArgument = $this->simpleValueArgument->setShortHelpMessage('Some Help Message');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame('Some Help Message', $this->simpleValueArgument->getShortHelpMessage());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setShortHelpMessageShouldThrowExceptionIfMessageIsNoString() {
		$this->simpleValueArgument->setShortHelpMessage(NULL);
	}

	/**
	 * @test
	 */
	public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue() {
		$returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame('default', $this->simpleValueArgument->getDefaultValue());
	}

	/**
	 * @test
	 */
	public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator() {
		$mockValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface');
		$returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame($mockValidator, $this->simpleValueArgument->getValidator());
	}

	/**
	 * @test
	 */
	public function setValueProvidesFluentInterface() {
		$returnedArgument = $this->simpleValueArgument->setValue(NULL);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}


	/**
	 * @test
	 */
	public function setValueUsesNullAsIs() {
		$this->simpleValueArgument = new \TYPO3\FLOW3\MVC\Controller\Argument('dummy', 'string');
		$this->simpleValueArgument->setValue(NULL);
		$this->assertNull($this->simpleValueArgument->getValue());
	}

	/**
	 * @test
	 */
	public function setValueUsesMatchingInstanceAsIs() {
		$this->mockPropertyMapper->expects($this->never())->method('convert');
		$this->objectArgument->setValue(new \DateTime());
	}

	protected function setupPropertyMapperAndSetValue() {
		$this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will($this->returnValue('convertedValue'));
		$this->mockPropertyMapper->expects($this->once())->method('getMessages')->will($this->returnValue(new \TYPO3\FLOW3\Error\Result()));
		return $this->simpleValueArgument->setValue('someRawValue');
	}

	/**
	 * @test
	 */
	public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue() {
		$this->setupPropertyMapperAndSetValue();
		$this->assertSame('convertedValue', $this->simpleValueArgument->getValue());
		$this->assertTrue($this->simpleValueArgument->isValid());
	}

	/**
	 * @test
	 */
	public function setValueShouldBeFluentInterface() {
		$this->assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
	}

	/**
	 * @test
	 */
	public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed() {
		$error = new \TYPO3\FLOW3\Error\Error('Some Error', 1234);

		$mockValidator = $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface');
		$validationMessages = new \TYPO3\FLOW3\Error\Result();
		$validationMessages->addError($error);
		$mockValidator->expects($this->once())->method('validate')->with('convertedValue')->will($this->returnValue($validationMessages));

		$this->simpleValueArgument->setValidator($mockValidator);
		$this->setupPropertyMapperAndSetValue();
		$this->assertFalse($this->simpleValueArgument->isValid());
		$this->assertEquals(array($error), $this->simpleValueArgument->getValidationResults()->getErrors());
	}

	/**
	 * @test
	 */
	public function defaultPropertyMappingConfigurationShouldBeFetchable() {
		$this->assertSame($this->mockConfiguration, $this->simpleValueArgument->getPropertyMappingConfiguration());
	}
}
?>