<?php
namespace TYPO3\FLOW3\Tests\Functional\AOP;

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
 * Test suite for aop proxy classes
 *
 */
class AopProxyTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function advicesAreExecutedAgainIfAnOverriddenMethodCallsItsParentMethod() {
		$targetClass = new Fixtures\ChildClassOfTargetClass01();
		$this->assertEquals('Greetings, I just wanted to say: Hello World World', $targetClass->sayHello());
	}

	/**
	 * @test
	 */
	public function anAdvicedParentMethodIsCalledCorrectlyIfANonAdvicedOverridingMethodCallsIt() {
		$targetClass = new Fixtures\ChildClassOfTargetClass01();
		$this->assertEquals('Two plus two makes five! For big twos and small fives! That was smart, eh?', $targetClass->saySomethingSmart());
	}

	/**
	 * @test
	 */
	public function methodArgumentsWithValueNullArePassedToTheProxiedMethod() {
		$proxiedClass = new Fixtures\EntityWithOptionalConstructorArguments('argument1', NULL, 'argument3');

		$this->assertEquals('argument1', $proxiedClass->argument1);
		$this->assertNull($proxiedClass->argument2);
		$this->assertEquals('argument3', $proxiedClass->argument3);
	}

	/**
	 * @test
	 */
	public function advicesOfAConcreteMethodInAnAbstractClassAreActiveInTheSubClassIfTheConcreteMethodWasNotOverriden() {
		$proxiedClass = new Fixtures\SubClassOfAbstractClass();
		$this->assertEquals('foo: bar adviced', $proxiedClass->concreteMethod('bar'));
	}

}
?>