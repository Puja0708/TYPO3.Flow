<?php
namespace TYPO3\FLOW3\Tests\Unit\Reflection;

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
 * Testcase for PropertyReflection
 *
 */
class PropertyReflectionTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	public $publicProperty = 'I\'m public';

	/**
	 * @var string
	 */
	protected $protectedProperty = 'abc';

	/**
	 * @var string
	 */
	private $privateProperty = '123';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception
	 */
	public function getValueThrowsAnExceptionOnReflectingANonObject() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
		$reflectionProperty->getValue(__CLASS__);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueReturnsValueOfAPublicProperty() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'publicProperty');
		$this->assertEquals('I\'m public', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a public property.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueEvenReturnsValueOfAProtectedProperty() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
		$this->assertEquals('abc', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a protected property.');

		$this->protectedProperty = 'def';
		$this->assertEquals('def', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "def".');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueReturnsValueOfAProtectedPropertyEvenIfItIsAnObject() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
		$this->protectedProperty = new \ArrayObject(array('1', '2', '3'));
		$this->assertEquals($this->protectedProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our protected property.');

		$this->protectedProperty = $this;
		$this->assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueEvenSetsValueOfAPublicProperty() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'publicProperty');
		$reflectionProperty->setValue($this, 'modified');
		$this->assertEquals('modified', $this->publicProperty, 'ReflectionProperty->setValue() did not successfully set the value of a public property.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValueEvenReturnsValueOfAPrivateProperty() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'privateProperty');
		$this->assertEquals('123', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a private property.');

		$this->privateProperty = '456';
		$this->assertEquals('456', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "456".');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValueReturnsValueOfAPrivatePropertyEvenIfItIsAnObject() {
		$reflectionProperty = new \TYPO3\FLOW3\Reflection\PropertyReflection(__CLASS__, 'privateProperty');
		$this->protectedProperty = new \ArrayObject(array('1', '2', '3'));
		$this->assertEquals($this->privateProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our private property.');

		$this->privateProperty = $this;
		$this->assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
	}
}
?>