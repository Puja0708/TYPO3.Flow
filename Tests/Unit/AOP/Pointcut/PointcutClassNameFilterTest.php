<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/Fixtures/DummyClass.php');
require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/Fixtures/SecondDummyClass.php');

/**
 * Testcase for the Pointcut Class Filter
 *
 */
class PointcutClassNameFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Checks if the class filter fires on a concrete and simple class expression
	 *
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isClassFinal')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->any())->method('isMethodFinal')->will($this->returnValue(FALSE));

		$classFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('TYPO3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 1');

		$classFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('.*Virtual.*');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 2');

		$classFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('TYPO3\Firtual.*');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($classFilter->matches('TYPO3\Virtual\Foo\Bar', '', '', 1), 'No. 3');
	}

	/**
	 * Checks if the class filter ignores classes declared "final"
	 *
	 * @test
	 */
	public function matchesIgnoresFinalClasses() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			final class $className { }"
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassFinal'), array(), '', FALSE);
		$mockReflectionService->expects($this->atLeastOnce())->method('isClassFinal')->will($this->returnValue(TRUE));

		$classFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('TYPO3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($classFilter->matches($className, '', '', 1));
	}

	/**
	 * Checks if the class filter ignores classes declared "final"
	 *
	 * @test
	 */
	public function matchesIgnoresClassesWithFinalConstructors() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				final public function __construct() {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassFinal', 'isMethodFinal'), array(), '', FALSE);
		$mockReflectionService->expects($this->atLeastOnce())->method('isClassFinal')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->atLeastOnce())->method('isMethodFinal')->with($className, '__construct')->will($this->returnValue(TRUE));

		$classFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('TYPO3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($classFilter->matches($className, '', '', 1));
	}
}
?>