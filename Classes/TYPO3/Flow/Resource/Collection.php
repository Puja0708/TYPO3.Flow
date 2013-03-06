<?php
namespace TYPO3\Flow\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Target\TargetInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * A resource collection
 */
class Collection {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $sources;

	/**
	 * @var \TYPO3\Flow\Resource\Target\TargetInterface
	 */
	protected $target;

	/**
	 * Constructor
	 *
	 * @param string $name User-space name of this collection, as specified in the settings
	 * @param array $sources A numeric array of source definitions
	 * @param \TYPO3\Flow\Resource\Target\TargetInterface $target The publication target for this collection
	 */
	public function __construct($name, array $sources, TargetInterface $target) {
		$this->name = $name;
		$this->sources = $sources;
		$this->target = $target;
	}

	/**
	 * Returns the name of this collection
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the publication target defined for this collection
	 *
	 * @return \TYPO3\Flow\Resource\Target\TargetInterface
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * If this collection contains only one or more sources based on the same storage,
	 * this method will return that storage object.
	 *
	 * If multiple storages are used, FALSE is returned.
	 *
	 * @return mixed Either the storage object or FALSE
	 */
	public function getStorage() {
		if (count($this->sources) !== 1) {
			return FALSE;
		}
		$source = current($this->sources);
		return $source['storage'];
	}

	/**
	 * Returns a list of directories defined for this collection
	 *
	 * @return array
	 */
	public function getDirectories() {
		$directories = array();
		foreach ($this->sources as $source) {
			$directoryPattern = isset($source['directories']) ? $source['directories'] : NULL;
			$directories = Arrays::arrayMergeRecursiveOverrule($directories, $source['storage']->getDirectories($directoryPattern));
		}
		return $directories;
	}

	/**
	 * Imports a resource (file) from the given URI into this collection.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * Note that this collection must have a single source defined in order to be able
	 * to import resources.
	 *
	 * @param string $uri The URI (or local path and filename) to import the resource from
	 * @return mixed A resource object representing the imported resource or a string containing an error message if an error ocurred
	 */
	public function importResource($uri) {
		$storage = $this->getStorage();
		if ($storage === FALSE) {
			$reason = (count($this->sources) > 1) ? 'more than 1' : 'no';
			return sprintf('The collection "%s" has %s configured sources. Note that a collection must have exactly 1 source in order to import new resources.', $this->name, $reason);
		}

		$resource = $storage->importResource($uri);
		if ($resource instanceof Resource) {
			$this->target->publishResource($resource, $storage);
		}
		return $resource;
	}

	/**
	 * Imports a resource (file) from the given upload info array into this collection.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * Note that this collection must have a single source defined in order to be able
	 * to import resources.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @return mixed A resource object representing the imported resource or a string containing an error message if an error ocurred
	 */
	public function importUploadedResource(array $uploadInfo) {
		$storage = $this->getStorage();
		if ($storage === FALSE) {
			$reason = (count($this->sources) > 1) ? 'more than 1' : 'no';
			return sprintf('The collection "%s" has %s configured sources. Note that a collection must have exactly 1 source in order to import new resources.', $this->name, $reason);
		}
		$resource = $storage->importUploadedResource($uploadInfo);
		if ($resource instanceof Resource) {
			$this->target->publishResource($resource, $storage);
		}
		return $resource;
	}

}
?>