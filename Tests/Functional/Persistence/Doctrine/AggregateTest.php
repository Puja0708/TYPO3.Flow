<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Doctrine;

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

use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment;

/**
 * Testcase for aggregate-related behavior
 */
class AggregateTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository;
	 */
	protected $postRepository;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\CommentRepository;
	 */
	protected $commentRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->postRepository = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository');
		$this->commentRepository = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\CommentRepository');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function entitiesWithinAggregateAreRemovedAutomaticallyWithItsRootEntity() {
		$image = new Image();
		$post = new Post();
		$post->setImage($image);

		$this->postRepository->add($post);
		$this->persistenceManager->persistAll();

		$imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

		$retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, 'TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image');
		$this->assertSame($image, $retrievedImage);

		$this->postRepository->remove($post);
		$this->persistenceManager->persistAll();

		$retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, 'TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image');
		$this->assertNull($retrievedImage);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function entitiesWithOwnRepositoryAreNotRemovedIfRelatedRootEntityIsRemoved() {
		$comment = new Comment();
		$this->commentRepository->add($comment);

		$post = new Post();
		$post->setComment($comment);

		$this->postRepository->add($post);
		$this->persistenceManager->persistAll();

		$commentIdentifier = $this->persistenceManager->getIdentifierByObject($comment);

		$retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, 'TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment');
		$this->assertSame($comment, $retrievedComment);

		$this->postRepository->remove($post);
		$this->persistenceManager->persistAll();

		$retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, 'TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment');
		$this->assertSame($comment, $retrievedComment);
	}

}
?>