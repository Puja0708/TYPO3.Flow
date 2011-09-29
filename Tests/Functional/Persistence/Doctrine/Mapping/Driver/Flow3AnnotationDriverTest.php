<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Doctrine\Mapping\Driver;

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
 * Testcase for ORM annotation driver
 */
class Flow3AnnotationDriverTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lifecycleEventAnnotationsAreDetected() {
		$classMetadataInfo = new \Doctrine\ORM\Mapping\ClassMetadataInfo('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadataInfo);
		$this->assertTrue($classMetadataInfo->hasLifecycleCallbacks('prePersist'));
	}

	/**
	 * Makes sure that
	 * - thumbnail and image (same type) do get distinct column names
	 * - simple properties get mapped to their name
	 * - using joincolumn without name on single associations uses the property name
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function columnNamesAreBuiltCorrectly() {
		$expectedTitleMapping = array(
			'fieldName' => 'title',
			'columnName' => 'title',
			'targetEntity' => 'string',
			'nullable' => TRUE,
			'type' => 'string',
		);

		$expectedImageAssociationMapping = array(
			'fieldName' => 'image',
			'columnName' => 'image',
			'joinColumns' => array (
				0 => array (
					'name' => 'image',
					'referencedColumnName' => 'flow3_persistence_identifier',
					'unique' => TRUE,
				),
			),
			'joinColumnFieldNames' => array(
				'image' => 'image',
			),
		);

		$expectedCommentAssociationMapping = array(
			'fieldName' => 'comment',
			'columnName' => 'comment',
			'joinColumns' => array(0 => array (
					'name' => 'comment',
					'referencedColumnName' => 'flow3_persistence_identifier',
					'unique' => TRUE,
					'nullable' => TRUE,
					'onDelete' => 'SET NULL',
					'onUpdate' => NULL,
					'columnDefinition' => NULL,
				),
			),
			'sourceEntity' => 'TYPO3\\FLOW3\\Tests\\Functional\\Persistence\\Fixtures\\Post',
			'sourceToTargetKeyColumns' => array (
				'comment' => 'flow3_persistence_identifier',
			),
			'joinColumnFieldNames' => array (
				'comment' => 'comment',
			),
			'targetToSourceKeyColumns' => array (
				'flow3_persistence_identifier' => 'comment',
			),
		);

		$classMetadataInfo = new \Doctrine\ORM\Mapping\ClassMetadataInfo('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadataInfo);

		$this->assertEquals($expectedTitleMapping, $classMetadataInfo->getFieldMapping('title'), 'mapping for "title" not as expected');
		$imageAssociationMapping = $classMetadataInfo->getAssociationMapping('image');
		$thumbnailAssociationMapping = $classMetadataInfo->getAssociationMapping('thumbnail');
		foreach (array_keys($expectedImageAssociationMapping) as $key) {
			$this->assertEquals($expectedImageAssociationMapping[$key], $imageAssociationMapping[$key], 'mapping for "image" not as expected');
			$this->assertNotEquals($expectedImageAssociationMapping[$key], $thumbnailAssociationMapping[$key], 'mapping for "thumbnail" not as expected');
		}

		$commentAssociationMapping = $classMetadataInfo->getAssociationMapping('comment');
		$this->assertEquals(1, count($commentAssociationMapping['joinColumns']));
		foreach (array_keys($expectedCommentAssociationMapping) as $key) {
			$this->assertEquals($expectedCommentAssociationMapping[$key], $commentAssociationMapping[$key], 'mapping for "comment" not as expected');
		}
	}

	/**
	 * The "related_post_id" column given manually must be kept.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function joinColumnAnnotationsAreObserved() {
		$expectedRelatedAssociationMapping = array(
			'fieldName' => 'related',
			'columnName' => 'related',
			'joinTable' => array(
				'name' => 'typo3_flow3_tests_functional_persistence_fixt_3ebc7_related_join',
				'schema' => NULL,
				'joinColumns' => array(
					0 => array(
						'name' => 'flow3_fixtures_post',
						'referencedColumnName' => 'flow3_persistence_identifier',
					),
				),
				'inverseJoinColumns' => array(
					0 => array(
						'name' => 'related_post_id',
						'referencedColumnName' => 'flow3_persistence_identifier',
						'unique' => FALSE,
						'nullable' => TRUE,
						'onDelete' => NULL,
						'onUpdate' => NULL,
						'columnDefinition' => NULL,
					),
				),
			),
			'relationToSourceKeyColumns' => array(
				'flow3_fixtures_post' => 'flow3_persistence_identifier',
			),
			'joinTableColumns' => array(
				0 => 'flow3_fixtures_post',
				1 => 'related_post_id',
			),
			'relationToTargetKeyColumns' => array(
				'related_post_id' => 'flow3_persistence_identifier',
			),
		);
		$classMetadataInfo = new \Doctrine\ORM\Mapping\ClassMetadataInfo('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadataInfo);

		$relatedAssociationMapping = $classMetadataInfo->getAssociationMapping('related');
		foreach (array_keys($expectedRelatedAssociationMapping) as $key) {
			$this->assertEquals($expectedRelatedAssociationMapping[$key], $relatedAssociationMapping[$key]);
		}
	}

}

?>