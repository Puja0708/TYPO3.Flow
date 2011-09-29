<?php
namespace TYPO3\FLOW3\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder
 */
class PropertyMappingConfigurationBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 *
	 * @var \TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	public function setUp() {
		$this->propertyMappingConfigurationBuilder = new \TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration() {
		$defaultConfiguration = $this->propertyMappingConfigurationBuilder->build();
		$this->assertTrue($defaultConfiguration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertTrue($defaultConfiguration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

		$this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', \TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
	}
}
?>