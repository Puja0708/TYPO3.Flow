<?php
namespace TYPO3\Flow\Resource\Target;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for a resource publishing target
 */
use TYPO3\Flow\Resource\Collection;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\Storage\StorageInterface;

interface TargetInterface {

	/**
	 * Returns the name of this target instance
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Publishes the whole collection to this target
	 *
	 * @param \TYPO3\Flow\Resource\Collection $collection The collection to publish
	 * @return void
	 */
	public function publish(Collection $collection);

	/**
	 * Returns the web accessible URI pointing to the given static resource
	 *
	 * @param string $relativePathAndFilename Relative path and filename of the static resource
	 * @return string The URI
	 */
	public function getPublicStaticResourceUri($relativePathAndFilename);

	/**
	 * Publishes the given persistent resource from the given storage
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @param \TYPO3\Flow\Resource\Storage\StorageInterface $storage The storage the given resource is stored in
	 * @return boolean
	 */
	public function publishResource(Resource $resource, StorageInterface $storage);

	/**
	 * Returns the web accessible URI pointing to the specified persistent resource
	 *
	 * @param string $resource Resource object or the resource hash of the resource
	 * @return string The URI
	 */
	public function getPublicPersistentResourceUri($resource);

}

?>