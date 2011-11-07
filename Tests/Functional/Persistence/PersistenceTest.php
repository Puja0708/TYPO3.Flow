<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository;

/**
 * Testcase for persistence
 *
 */
class PersistenceTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->testEntityRepository = new TestEntityRepository();
	}

	/**
	 * @test
	 */
	public function entitiesArePersistedAndReconstituted() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$this->assertEquals('FLOW3', $testEntity->getName());
	}

	/**
	 * @test
	 */
	public function executingAQueryWillOnlyExecuteItLazily() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertInstanceOf('TYPO3\FLOW3\Persistence\Doctrine\QueryResult', $allResults);
		$this->assertAttributeInternalType('null', 'rows', $allResults, 'Query Result did not load the result collection lazily.');

		$allResultsArray = $allResults->toArray();
		$this->assertEquals('FLOW3', $allResultsArray[0]->getName());
		$this->assertAttributeInternalType('array', 'rows', $allResults);
	}

	/**
	 * @test
	 */
	public function serializingAQueryResultWillResetCachedResult() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertAttributeInternalType('null', 'rows', $unserializedResults, 'Query Result did not flush the result collection after serialization.');
	}

	/**
	 * @test
	 */
	public function resultCanStillBeTraversedAfterSerialization() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals(1, count($allResults->toArray()), 'Not correct number of entities found before running test.');

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertEquals(1, count($unserializedResults->toArray()));
		$this->assertEquals('FLOW3', $unserializedResults[0]->getName());
	}

	/**
	 * @test
	 */
	public function getFirstShouldNotHaveSideEffects() {
		$this->removeExampleEntities();
		$this->insertExampleEntity('FLOW3');
		$this->insertExampleEntity('TYPO3');

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals('FLOW3', $allResults->getFirst()->getName());

		$numberOfTotalResults = count($allResults->toArray());
		$this->assertEquals(2, $numberOfTotalResults);
	}

	/**
	 * @test
	 */
	public function aClonedEntityWillGetANewIdentifier() {
		$testEntity = new TestEntity();
		$firstIdentifier = $this->persistenceManager->getIdentifierByObject($testEntity);

		$clonedEntity = clone $testEntity;
		$secondIdentifier = $this->persistenceManager->getIdentifierByObject($clonedEntity);
		$this->assertNotEquals($firstIdentifier, $secondIdentifier);
	}

	/**
	 * @test
	 */
	public function persistedEntitiesLyingInArraysAreNotSerializedButReferencedByTheirIdentifierAndReloadedFromPersistenceOnWakeup() {
		$testEntityLyingInsideTheArray = new TestEntity();
		$testEntityLyingInsideTheArray->setName('FLOW3');

		$arrayProperty = array(
			'some' => array(
				'nestedArray' => array(
					'key' => $testEntityLyingInsideTheArray
				)
			)
		);

		$testEntityWithArrayProperty = new TestEntity();
		$testEntityWithArrayProperty->setArrayProperty($arrayProperty);

		$this->testEntityRepository->add($testEntityLyingInsideTheArray);
		$this->testEntityRepository->add($testEntityWithArrayProperty);

		$this->persistenceManager->persistAll();

		$serializedData = serialize($testEntityWithArrayProperty);

		$testEntityLyingInsideTheArray->setName('TYPO3');
		$this->persistenceManager->persistAll();

		$testEntityWithArrayPropertyUnserialized = unserialize($serializedData);
		$arrayPropertyAfterUnserialize = $testEntityWithArrayPropertyUnserialized->getArrayProperty();

		$this->assertNotSame($testEntityWithArrayProperty, $testEntityWithArrayPropertyUnserialized);
		$this->assertEquals('TYPO3', $arrayPropertyAfterUnserialize['some']['nestedArray']['key']->getName(), 'The entity inside the array property has not been updated to the current persistend state after wakeup.');
	}

	/**
	 * @test
	 */
	public function newEntitiesWhichAreNotAddedToARepositoryYetAreAlreadyKnownToGetObjectByIdentifier() {
		$expectedEntity = new TestEntity();
		$uuid = $this->persistenceManager->getIdentifierByObject($expectedEntity);
		$actualEntity = $this->persistenceManager->getObjectByIdentifier($uuid, 'TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$this->assertSame($expectedEntity, $actualEntity);
	}


	/**
	 * Helper which inserts example data into the database.
	 *
	 * @param string $name
	 */
	protected function insertExampleEntity($name = 'FLOW3') {
		$testEntity = new TestEntity;
		$testEntity->setName($name);
		$this->testEntityRepository->add($testEntity);

		// FIXME this was tearDownPersistence(), which would reset objects in memory to a pristine state as well
		$this->persistenceManager->persistAll();
	}

	/**
	 * Remove all example entities to enforce a clean state
	 */
	protected function removeExampleEntities() {
		$this->testEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
	}
}
?>