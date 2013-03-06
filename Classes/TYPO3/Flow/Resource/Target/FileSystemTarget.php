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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Collection;
use TYPO3\Flow\Resource\Exception;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\Storage\FileSystemStorage;
use TYPO3\Flow\Resource\Storage\StorageInterface;
use TYPO3\Flow\Utility\Files;

/**
 * A target which publishes resources to a specific directory in a file system.
 */
class FileSystemTarget implements TargetInterface {

	/**
	 * Name which identifies this publishing target
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The path (in a filesystem) where resources are published to
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Publicly accessible web URI which points to the root path of this target.
	 * Can be relative to website's base Uri, for example "_Resources/Static/"
	 *
	 * @var string
	 */
	protected $baseUri = '';

	/**
	 * If resources should be copied ("copy") or symlinked ("link") in order to be
	 * published
	 *
	 * @var string
	 */
	protected $mirrorMode = 'link';

	/**
	 * Internal cache for known storages, indexed by storage name
	 *
	 * @var array<\TYPO3\Flow\Resource\Storage\StorageInterface>
	 */
	protected $storages = array();

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * Constructor
	 *
	 * @param string $name Name of this target instance, according to the resource settings
	 * @param array $options Options for this target
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		foreach ($options as $key => $value) {
			switch ($key) {
				case 'baseUri':
				case 'path':
					$this->$key = $value;
				break;
				case 'mirrorMode':
					if ($value !== 'link' && $value !== 'copy') {
						throw new Exception(sprintf('The option "%s" was set to the unknonw value "%s" in the configuration of a resource FileSystemTarget. Please check your settings.', $key, $value), 1361525953);
					}
					$this->mirrorMode = $value;
				break;
				default:
					throw new Exception(sprintf('An unknown option "%s" was specified in the configuration of a resource FileSystemTarget. Please check your settings.', $key), 1361525952);
			}
		}
	}

	/**
	 * Initializes this resource target
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Resource\Exception
	 */
	public function initializeObject() {
		if (!is_writable($this->path)) {
			@Files::createDirectoryRecursively($this->path);
		}
		if (!is_dir($this->path) && !is_link($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a publishing target does not exist and could not be created.', 1207124538);
		}
		if (!is_writable($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a publishing target is not writable.', 1207124546);
		}
	}

	/**
	 * Returns the name of this target instance
	 *
	 * @return string The target instance name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Publishes the whole collection to this target
	 *
	 * @param \TYPO3\Flow\Resource\Collection $collection The collection to publish
	 * @return void
	 */
	public function publish(Collection $collection) {
		foreach ($collection->getDirectories() as $directoryStorageUri) {
			list($storageName, $sourcePath) = explode('://', $directoryStorageUri);
			if (!isset($this->storages[$storageName])) {
				$this->storages[$storageName] = $this->resourceManager->getStorage($storageName);
			}
			$this->publishDirectory($this->storages[$storageName]->getPrivateUriByResourcePath($sourcePath), $sourcePath);
		}
	}

	/**
	 * Returns the web accessible URI pointing to the given static resource
	 *
	 * @param string $relativePathAndFilename Relative path and filename of the static resource
	 * @return string The URI
	 */
	public function getPublicStaticResourceUri($relativePathAndFilename) {
		return $this->baseUri . $relativePathAndFilename;
	}

	/**
	 * Publishes the given persistent resource from the given storage
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @param \TYPO3\Flow\Resource\Storage\StorageInterface $storage The storage the given resource is stored in
	 * @return boolean
	 */
	public function publishResource(Resource $resource, StorageInterface $storage) {
		$sourcePathAndFilename = $storage->getPrivateUriByResource($resource);
		if ($sourcePathAndFilename === FALSE) {
			return FALSE;
		}
		return $this->publishFile($sourcePathAndFilename,  $resource->getHash(), ($storage instanceof FileSystemStorage ? $this->mirrorMode : 'copy'));
	}

	/**
	 * Returns the web accessible URI pointing to the specified persistent resource
	 *
	 * @param string $resource Resource object or the resource hash of the resource
	 * @return string The URI
	 */
	public function getPublicPersistentResourceUri($resource) {
		if ($resource instanceof Resource) {
			return $this->baseUri . $resource->getHash();
		}
		if (!is_string($resource) || strlen($resource) !== 40) {
			throw new \InvalidArgumentException('Specified an invalid resource to getPublishedPersistentResourceUri()', 1362495360);
		}
		return $this->baseUri . $resource;
	}

	/**
	 * Publishes the specified source file to this target, with the given relative path.
	 *
	 * @param string $sourcePathAndfilename Path and name of the source file
	 * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
	 * @param string $mirrorMode If the file should be linked or copied
	 * @return boolean TRUE if publishing succeeded
	 */
	protected function publishFile($sourcePathAndFilename, $relativeTargetPathAndFilename, $mirrorMode = 'copy') {
		$targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

		if ($mirrorMode === 'link') {
			if (Files::is_link($targetPathAndFilename)) {
				return TRUE;
			} elseif (file_exists($targetPathAndFilename)) {
				unlink($targetPathAndFilename);
			}
			symlink($sourcePathAndFilename, $targetPathAndFilename);
		} else {
			if (file_exists($targetPathAndFilename) && filemtime($sourcePathAndFilename) === $targetPathAndFilename) {
				return TRUE;
			}
			copy($sourcePathAndFilename, $targetPathAndFilename);
			return TRUE;
		}
	}

	/**
	 * Publishes the specified source directory to this target, with the given
	 * relative path.
	 *
	 * @param string $sourcePath Path of the source directory
	 * @param string $relativeTargetPath relative path in the target directory
	 * @return boolean TRUE if publishing succeeded
	 */
	protected function publishDirectory($sourcePath, $relativeTargetPath) {
		$normalizedSourcePath = rtrim(Files::getUnixStylePath($this->realpath($sourcePath)), '/');
		$targetPath = rtrim(Files::concatenatePaths(array($this->path, $relativeTargetPath)), '/');

		if ($this->mirrorMode === 'link') {
			if (Files::is_link($targetPath) && (rtrim(Files::getUnixStylePath($this->realpath($targetPath)), '/') === $normalizedSourcePath)) {
				return TRUE;
			} elseif (is_dir($targetPath)) {
				Files::removeDirectoryRecursively($targetPath);
			} elseif (is_link($targetPath)) {
				unlink($targetPath);
			} else {
				Files::createDirectoryRecursively(dirname($targetPath));
			}
			symlink($sourcePath, $targetPath);
		} else {
			Files::copyDirectoryRecursively($sourcePath, $targetPath, TRUE);
			return TRUE;
		}
	}

	/**
	 * Wrapper around realpath(). Needed for testing, as realpath() cannot be mocked
	 * by vfsStream.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function realpath($path) {
		return realpath($path);
	}
}

?>