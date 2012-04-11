<?php
namespace TYPO3\FLOW3\Tests\Unit\Aop\Pointcut;

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
 * Testcase for the Pointcut Method-Tagged-With Filter
 *
 */
class PointcutMethodTaggedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenTag() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getMethodTagsValues'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->any())->method('getMethodTagsValues')->with(__CLASS__, __FUNCTION__)->will($this->onConsecutiveCalls(array('SomeTag' => array(), 'OtherTag' => array('foo')), array()));

		$filter = new \TYPO3\FLOW3\Aop\Pointcut\PointcutMethodTaggedWithFilter('SomeTag');
		$filter->injectReflectionService($mockReflectionService);

		$this->assertTrue($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
		$this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
	}

	/**
	 * @test
	 */
	public function matchesReturnsFalseIfMethodDoesNotExistOrDeclardingClassHasNotBeenSpecified() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE, TRUE);

		$filter = new \TYPO3\FLOW3\Aop\Pointcut\PointcutMethodTaggedWithFilter('Acme\Some\Annotation');
		$filter->injectReflectionService($mockReflectionService);

		$this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, NULL, 1234));
		$this->assertFalse($filter->matches(__CLASS__, 'foo', __CLASS__, 1234));
	}
}
?>