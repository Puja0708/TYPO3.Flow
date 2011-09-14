<?php
namespace TYPO3\FLOW3\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (__DIR__ . '/../../Fixtures/ClassWithSetters.php');
require_once (__DIR__ . '/../../Fixtures/ClassWithSettersAndConstructor.php');

/**
 * Testcase for the String to String converter
 *
 * @covers \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter<extended>
 */
class PersistentObjectConverterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Property\TypeConverterInterface
	 */
	protected $converter;

	protected $mockReflectionService;
	protected $mockPersistenceManager;
	protected $mockObjectManager;

	public function setUp() {
		$this->converter = new \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter();
		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$this->converter->injectReflectionService($this->mockReflectionService);

		$this->mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->converter->injectPersistenceManager($this->mockPersistenceManager);

		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->converter->injectObjectManager($this->mockObjectManager);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	public function dataProviderForCanConvert() {
		return array(
			array(TRUE, FALSE, TRUE), // is entity => can convert
			array(FALSE, TRUE, TRUE), // is valueobject => can convert
			array(FALSE, FALSE, FALSE) // is no entity and no value object => can not convert
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForCanConvert
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected) {
		$this->mockReflectionService->expects($this->at(0))->method('isClassTaggedWith')->with('TheTargetType', 'valueobject')->will($this->returnValue($isValueObject));
		$this->mockReflectionService->expects($this->at(1))->method('isClassTaggedWith')->with('TheTargetType', 'entity')->will($this->returnValue($isEntity));

		$this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
	}

	/**
	 * test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyNamesReturnsEmptyArray() {
		$this->assertEquals(array(), $this->converter->getPropertyNames('myString'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getSourceChildPropertiesToBeConvertedReturnsAllPropertiesExceptTheIdentityProperty() {
		$source = array(
			'k1' => 'v1',
			'__identity' => 'someIdentity',
			'k2' => 'v2'
		);
		$expected = array(
			'k1' => 'v1',
			'k2' => 'v2'
		);
		$this->assertEquals($expected, $this->converter->getSourceChildPropertiesToBeConverted($source));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType() {
		$mockSchema = $this->getMockBuilder('TYPO3\FLOW3\Reflection\ClassSchema')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('TheTargetType')->will($this->returnValue($mockSchema));

		$mockSchema->expects($this->any())->method('hasProperty')->with('thePropertyName')->will($this->returnValue(TRUE));
		$mockSchema->expects($this->any())->method('getProperty')->with('thePropertyName')->will($this->returnValue(array(
			'type' => 'TheTypeOfSubObject',
			'elementType' => NULL
		)));
		$configuration = $this->buildConfiguration(array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeOfChildPropertyShouldUseConfiguredTypeIfItWasSet() {
		$this->mockReflectionService->expects($this->never())->method('getClassSchema');

		$configuration = $this->buildConfiguration(array());
		$configuration->forProperty('thePropertyName')->setTypeConverterOption('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, 'Foo\Bar');
		$this->assertEquals('Foo\Bar', $this->converter->getTypeOfChildProperty('foo', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfUuidStringIsGiven() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();


		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfNonUuidStringIsGiven() {
		$identifier = 'someIdentifier';
		$object = new \stdClass();

		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfOnlyIdentityArrayGiven() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();

		$source = array(
			'__identity' => $identifier
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($source, 'MySpecialType'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeModifiedButConfigurationIsNotSet() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();
		$object->someProperty = 'asdf';

		$source = array(
			'__identity' => $identifier,
			'foo' => 'bar'
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->converter->convertFrom($source, 'MySpecialType');
	}

	/**
	 * @param array $typeConverterOptions
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 */
	protected function buildConfiguration($typeConverterOptions) {
		$configuration = new \TYPO3\FLOW3\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOptions('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', $typeConverterOptions);
		return $configuration;
	}

	/**
	 * @param integer $numberOfResults
	 * @param Matcher $howOftenIsGetFirstCalled
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setupMockQuery($numberOfResults, $howOftenIsGetFirstCalled) {
		$mockClassSchema = $this->getMock('TYPO3\FLOW3\Reflection\ClassSchema', array(), array('Dummy'));
		$mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array('key1' => 'someType')));
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->with('SomeType')->will($this->returnValue($mockClassSchema));

		$mockConstraint = $this->getMockBuilder('TYPO3\FLOW3\Persistence\Generic\Qom\Comparison')->disableOriginalConstructor()->getMock();

		$mockObject = new \stdClass();
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQueryResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue($numberOfResults));
		$mockQueryResult->expects($howOftenIsGetFirstCalled)->method('getFirst')->will($this->returnValue($mockObject));
		$mockQuery->expects($this->once())->method('equals')->with('key1', 'value1')->will($this->returnValue($mockConstraint));
		$mockQuery->expects($this->once())->method('matching')->with($mockConstraint)->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('SomeType')->will($this->returnValue($mockQuery));

		return $mockObject;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldReturnFirstMatchingObjectIfMultipleIdentityPropertiesExist() {
		$mockObject = $this->setupMockQuery(1, $this->once());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$actual = $this->converter->convertFrom($source, 'SomeType');
		$this->assertSame($mockObject, $actual);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Property\Exception\TargetNotFoundException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldReturnExceptionIfNoMatchingObjectWasFound() {
		$this->setupMockQuery(0, $this->never());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$actual = $this->converter->convertFrom($source, 'SomeType');
		$this->assertNull($actual);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Property\Exception\DuplicateObjectException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldThrowExceptionIfMoreThanOneObjectWasFound() {
		$this->setupMockQuery(2, $this->never());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$actual = $this->converter->convertFrom($source, 'SomeType');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeCreatedButConfigurationIsNotSet() {
		$source = array(
			'foo' => 'bar'
		);
		$this->converter->convertFrom($source, 'MySpecialType');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObject() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new \TYPO3\FLOW3\Fixtures\ClassWithSetters();
		$convertedChildProperties = array(
			'property1' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('TYPO3\FLOW3\Fixtures\ClassWithSetters')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\FLOW3\Fixtures\ClassWithSetters', '__construct')->will($this->returnValue(array()));
		$configuration = $this->buildConfiguration(array(\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Property\Exception\InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfPropertyOnTargetObjectCouldNotBeSet() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new \TYPO3\FLOW3\Fixtures\ClassWithSetters();
		$convertedChildProperties = array(
			'propertyNotExisting' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('TYPO3\FLOW3\Fixtures\ClassWithSetters')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\FLOW3\Fixtures\ClassWithSetters', '__construct')->will($this->returnValue(array()));
		$configuration = $this->buildConfiguration(array(\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObjectWhenThereAreConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new \TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor('param1');
		$convertedChildProperties = array(
			'property1' => 'param1',
			'property2' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', 'param1')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$configuration = $this->buildConfiguration(array(\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
		$this->assertEquals('bar', $object->getProperty2());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObjectWhenThereAreOptionalConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new \TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor('param1');

		$this->mockObjectManager->expects($this->once())->method('create')->with('TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', 'thisIsTheDefaultValue')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => TRUE, 'defaultValue' => 'thisIsTheDefaultValue')
		)));
		$configuration = $this->buildConfiguration(array(\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', array(), $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Property\Exception\InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfRequiredConstructorParameterWasNotFound() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new \TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor('param1');
		$convertedChildProperties = array(
			'property2' => 'bar'
		);

		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$configuration = $this->buildConfiguration(array(\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function convertFromShouldReturnNullForEmptyString() {
		$source = '';
		$result = $this->converter->convertFrom($source, 'TYPO3\FLOW3\Fixtures\ClassWithSettersAndConstructor');
		$this->assertNull($result);
	}

}
?>