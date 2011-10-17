<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection;

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
 * Functional tests for the Dependency Injection features
 *
 */
class ReflectionServiceTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theReflectionServiceBuildsClassSchemataForEntities() {
		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$classSchema = $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture');

		$this->assertNotNull($classSchema);
		$this->assertSame('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', $classSchema->getClassName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function theReflectionServiceCorrectlyBuildsMethodTagsValues() {
		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$actual = $reflectionService->getMethodTagsValues('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', 'setName');

		$expected = array(
			'param' => array(
				'string $name'
			),
			'return' => array(
				'void'
			),
			'validate' => array(
				'$name", type="foo1',
				'$name", type="foo2'
			),
			'skipcsrfprotection' => array()
		);
		$this->assertSame($expected, $actual);
	}

	/**
	 * This test case checks for http://forge.typo3.org/issues/29449
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception\InvalidClassException
	 */
	public function theReflectionServiceThrowsExceptionIfDoctrineProxyIsReflected() {
		require_once(__DIR__ . '/Fixtures/BrokenClassImplementingDoctrineProxy.php');

		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$reflectionService->getClassTagsValues('\TYPO3\FLOW3\Persistence\Doctrine\Proxies\FakePackageDomainModelBrokenClassProxy'); // TODO
	}

	/**
	 * @test
	 */
	public function aggregateRootAssignmentsInHierarchiesAreCorrect() {
		/** @var $reflectionService \TYPO3\FLOW3\Reflection\ReflectionService */
		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');

		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Model\SuperEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Model\SubEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Model\SubSubSubEntity')->getRepositoryClassName());
	}
}
?>