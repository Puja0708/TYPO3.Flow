<?php
namespace TYPO3\FLOW3\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Reflection\ClassReflection;

/**
 * Functional tests for the Proxy Compiler and related features
 *
 */
class ProxyCompilerTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function proxyClassesStillContainAnnotationsFromItsOriginalClass() {
		$class = new ClassReflection('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA');
		$method = $class->getMethod('setSomeProperty');

		$this->assertTrue($class->implementsInterface('TYPO3\FLOW3\Object\Proxy\ProxyInterface'));
		$this->assertTrue($class->isTaggedWith('scope'));
		$this->assertTrue($method->isTaggedWith('session'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesAnnotatedWithProxyDisableAreNotProxied() {
		$singletonB = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB');
		$this->assertNotInstanceOf('TYPO3\FLOW3\Object\Proxy\ProxyInterface', $singletonB);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setInstanceOfSubClassDoesNotOverrideParentClass() {
		$singletonE = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassE');
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassE', get_class($singletonE));

		$singletonEsub = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassEsub');
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassEsub', get_class($singletonEsub));

		$singletonE2 = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassE');
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassE', get_class($singletonE2));
		$this->assertSame($singletonE, $singletonE2);
	}

}
?>