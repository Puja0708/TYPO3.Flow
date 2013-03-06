<?php
namespace TYPO3\Flow\Resource\Storage;

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
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\Exception;
use TYPO3\Flow\Utility\Files;

/**
 * A resource storage based on the (local) file system
 */
class FileSystemStorage implements StorageInterface {

	/**
	 * Name which identifies this resource storage
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The path (in a filesystem) where resources are stored
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * Constructor
	 *
	 * @param string $name Name of this storage instance, according to the resource settings
	 * @param array $options Options for this storage
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		foreach ($options as $key => $value) {
			switch ($key) {
				case 'path':
					$this->$key = $value;
				break;
				default:
					throw new Exception(sprintf('An unknown option "%s" was specified in the configuration of a resource FileSystemStorage. Please check your settings.', $key), 1361533187);
			}
		}
	}

	/**
	 * Initializes this resource storage
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Resource\Exception
	 */
	public function initializeObject() {
		if (!is_writable($this->path)) {
			Files::createDirectoryRecursively($this->path);
		}
		if (!is_dir($this->path) && !is_link($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a resource storage does not exist.', 1361533189);
		}
		if (!is_writable($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a resource storage is not writable.', 1361533190);
		}
	}

	/**
	 * Returns the instance name of this storage
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Imports a resource (file) as specified in the URI as a persistent resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param string $uri The URI pointing to the resource to import (can also be a local path and filename)
	 * @return mixed A resource object representing the imported resource or an error message if an error occurred
	 */
	public function importResource($uri) {
		$pathInfo = pathinfo($uri);
		$temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('TYPO3_Flow_ResourceImport_');
		if (copy($uri, $temporaryTargetPathAndFilename) === FALSE) {
			return sprintf('Could not copy thre file from "%s" to temporary file "%s".', $uri, $temporaryTargetPathAndFilename);
		}

		$hash = sha1_file($temporaryTargetPathAndFilename);
		$finalTargetPathAndFilename = $this->path . $hash;
		if (rename($temporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
			unlink($temporaryTargetPathAndFilename);
			return sprintf('The temporary file of the file import could not be moved to the final target "%s".', $finalTargetPathAndFilename);
		}

		$this->fixFilePermissions($finalTargetPathAndFilename);

		$resource = new Resource();
		$resource->setFilename($pathInfo['basename']);
		$resource->setHash($hash);

		return $resource;
	}

	/**
	 * Imports a resource (file) as specified in the given upload info array as a
	 * persistent resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @return mixed A resource object representing the imported resource or an error message if an error occurred
	 */
	public function importUploadedResource(array $uploadInfo) {
		$pathInfo = pathinfo($uploadInfo['name']);
		if (!file_exists($uploadInfo['tmp_name'])) {
			return 'The temporary file of the file upload does not exist (anymore).';
		}

		$hash = sha1_file($uploadInfo['tmp_name']);
		$finalTargetPathAndFilename = $this->path . $hash;
		if (move_uploaded_file($uploadInfo['tmp_name'], $finalTargetPathAndFilename) === FALSE) {
			return sprintf('The temporary file of the file upload could not be moved to the final target "%s".', $finalTargetPathAndFilename);
		}
		$this->fixFilePermissions($finalTargetPathAndFilename);

		$resource = new Resource();
		$resource->setFilename($pathInfo['basename']);
		$resource->setHash($hash);

		return $resource;
	}

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * The path and filename returned is always a regular, local path and filename.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource stored in this storage
	 * @return mixed A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResource(Resource $resource) {
		$pathAndFilename = $this->path . $resource->getHash();
		return (file_exists($pathAndFilename) ? $pathAndFilename : FALSE);
	}

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * The path and filename returned is always a regular, local path and filename.
	 *
	 * @param string $relativePath A path relative to the storage root, for example "MyFirstDirectory/SecondDirectory/Foo.css"
	 * @return mixed A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResourcePath($relativePath) {
		$pathAndFilename = $this->path . ltrim($relativePath, '/');
		return (file_exists($pathAndFilename) ? $pathAndFilename : FALSE);
	}

	/**
	 * Fixes the permissions as needed for Flow to run fine in web and cli context.
	 *
	 * @param string $pathAndFilename
	 * @return void
	 */
	protected function fixFilePermissions($pathAndFilename) {
		@chmod($pathAndFilename, 0666 ^ umask());
	}

}

?>