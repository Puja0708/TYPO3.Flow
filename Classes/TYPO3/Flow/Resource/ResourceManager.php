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

/**
 * The Resource Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ResourceManager {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $statusCache;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var string
	 */
	protected $persistentResourcesStorageBaseUri;

	/**
	 * @var \SplObjectStorage
	 */
	protected $importedResources;

	 /**
	  * @var array<\TYPO3\Flow\Resource\Storage\StorageInterface>
	  */
	 protected $storages;

	 /**
	  * @var array<\TYPO3\Flow\Resource\Target\TargetInterface>
	  */
	 protected $targets;

	 /**
	  * @var array<\TYPO3\Flow\Resource\Collection>
	  */
	 protected $collections;

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Check for implementations of TYPO3\Flow\Resource\Streams\StreamWrapperInterface and
	 * register them.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->initializeStreamWrapper();
		$this->initializeStorages();
		$this->initializeTargets();
		$this->initializeCollections();

			// For now this URI is hardcoded, but might be manageable in the future
			// if additional persistent resources storages are supported.
		$this->persistentResourcesStorageBaseUri = FLOW_PATH_DATA . 'Persistent/Resources/';
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->persistentResourcesStorageBaseUri);

		$this->importedResources = new \SplObjectStorage();
	}

	/**
	 * Creates a resource (file) from the given binary content as a persistent resource.
	 * On a successful creation this method returns a Resource object representing the
	 * newly created persistent resource.
	 *
	 * @param mixed $content The binary content of the file
	 * @param string $filename
	 * @return \TYPO3\Flow\Resource\Resource A resource object representing the created resource or FALSE if an error occurred.
	 * @api
	 * TODO: REFACTOR
	 */
	public function createResourceFromContent($content, $filename) {
		$pathInfo = pathinfo($filename);

		$hash = sha1($content);
		$finalTargetPathAndFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->persistentResourcesStorageBaseUri, $hash));
		if (!file_exists($finalTargetPathAndFilename)) {
			if (file_put_contents($finalTargetPathAndFilename, $content) === FALSE) {
				$this->systemLogger->log('Could not create resource at "' . $finalTargetPathAndFilename . '".', LOG_WARNING);
				return FALSE;
			} else {
				$this->fixFilePermissions($finalTargetPathAndFilename);
			}
		}

		$resource = $this->createResourceFromHashAndFilename($hash, $pathInfo['basename']);
		$this->attachImportedResource($resource);

		return $resource;
	}

	/**
	 * Imports a resource (file) from the given location as a persistent resource.
	 * On a successful import this method returns a Resource object representing the
	 * newly imported persistent resource.
	 *
	 * @param string $uri An URI (can also be a path and filename) pointing to the resource to import
	 * @param string $collectionName Name of the collection this uploaded resource should be added to
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 * @api
	 */
	public function importResource($uri, $collectionName = 'persistentResources') {
		if (!isset($this->collections[$collectionName])) {
			$this->systemLogger->log('Tried to import a file into the resource collection "%s" but no such collection exists. Please check your settings and the code which triggered the import.', LOG_WARNING);
			return FALSE;
		}

		$resource = $this->collections[$collectionName]->importResource($uri);
		if (is_string($resource)) {
			$this->systemLogger->log(sprintf('Importing a file into the resource collection "%s" failed: %s', $collectionName), LOG_ERR);
			return FALSE;
		}

		$this->importedResources->attach($resource);

		$this->systemLogger->log(sprintf('Successfully imported file "%s" into the resource collection "%s" (storage: %s / %s)', $uri, $collectionName, $this->collections[$collectionName]->getStorage()->getName(), get_class($this->collections[$collectionName]->getStorage())), LOG_DEBUG);
		return $resource;
	}

	/**
	 * Imports a resource (file) from the given upload info array as a persistent
	 * resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @param string $collectionName Name of the collection this uploaded resource should be added to
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 */
	public function importUploadedResource(array $uploadInfo, $collectionName) {
		if (!isset($this->collections[$collectionName])) {
			$this->systemLogger->log('Tried to import an uploaded file into the resource collection "%s" but no such collection exists. Please check your settings and HTML forms.', LOG_WARNING);
			return FALSE;
		}
		$resource = $this->collections[$collectionName]->importUploadedResource($uploadInfo);
		if (is_string($resource)) {
			$this->systemLogger->log(sprintf('Importing an uploaded file into the resource collection "%s" failed: %s', $collectionName), LOG_ERR);
			return FALSE;
		}

		$pathInfo = pathinfo($uploadInfo['name']);
		$this->importedResources[$resource] = array(
			'originalFilename' => $pathInfo['basename']
		);

		$this->systemLogger->log(sprintf('Successfully imported an uploaded file into the resource collection "%s" (storage: %s / %s)', $collectionName, $this->collections[$collectionName]->getStorage()->getName(), get_class($this->collections[$collectionName]->getStorage())), LOG_DEBUG);
		return $resource;
	}

	/**
	 * Returns an object storage with all resource objects which have been imported
	 * by the Resource Manager during this script call. Each resource comes with
	 * an array of additional information about its import.
	 *
	 * Example for a returned object storage:
	 *
	 * $resource1 => array('originalFilename' => 'Foo.txt'),
	 * $resource2 => array('originalFilename' => 'Bar.txt'),
	 * ...
	 *
	 * @return \SplObjectStorage
	 * @api
	 */
	public function getImportedResources() {
		return clone $this->importedResources;
	}

	/**
	 * Deletes the file represented by the given resource instance.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return boolean
	 * TODO: REFACTOR
	 */
	public function deleteResource($resource) {
		return FALSE;
	}

	/**
	 * Prepares a mirror of public package resources that is accessible through
	 * the web server directly.
	 *
	 * @return void
	 */
	public function publishPublicPackageResources() {
		$packageResourcesPublished = $this->statusCache->has('packageResourcesPublished');
		if ($this->settings['resource']['publishing']['detectPackageResourceChanges'] === FALSE && $packageResourcesPublished) {
			return;
		}

		$target = $this->collections['staticResources']->getTarget();
		$target->publish($this->collections['staticResources']);

		if ($packageResourcesPublished === FALSE) {
			$this->statusCache->set('packageResourcesPublished', 'y', array(\TYPO3\Flow\Cache\Frontend\FrontendInterface::TAG_PACKAGE));
		}
	}

	/**
	 * Returns the web accessible URI for the given resource object
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource object
	 * @return string A URI as a string
	 */
	public function getPublicPersistentResourceUri(Resource $resource) {
		$collectionName = $resource->getCollectionName();
		if (!isset($this->collections[$collectionName])) {
			return '404-Collection-Does-Not-Exist';
		}
		$target = $this->collections[$collectionName]->getTarget();
		return $target->getPublicPersistentResourceUri($resource->getHash());
	}

	/**
	 * @param $packageKey
	 * @param $relativePathAndFilename
	 * @return mixed
	 */
	public function getPublicPackageResourceUri($packageKey, $relativePathAndFilename) {
		$target = $this->collections['staticResources']->getTarget();
		return $target->getPublicStaticResourceUri($packageKey . '/Resources/Public/' . $relativePathAndFilename);
	}

	/**
	 * Returns a storage instance by the given name
	 *
	 * @param string $storageName Name of the storage as defined in the settings
	 * @return \TYPO3\Flow\Resource\Storage\StorageInterface
	 */
	public function getStorage($storageName) {
		return isset($this->storages[$storageName]) ? $this->storages[$storageName] : NULL;
	}

	/**
	 * Helper function which creates or fetches a resource pointer object for a given hash.
	 *
	 * If a ResourcePointer with the given hash exists, this one is used. Else, a new one
	 * is created. This is a workaround for missing ValueObject support in Doctrine.
	 *
	 * @param string $hash
	 * @return \TYPO3\Flow\Resource\ResourcePointer
	 */
	protected function getResourcePointerForHash($hash) {
		$resourcePointer = $this->persistenceManager->getObjectByIdentifier($hash, 'TYPO3\Flow\Resource\ResourcePointer');
		if (!$resourcePointer) {
			$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
			$this->persistenceManager->add($resourcePointer);
		}

		return $resourcePointer;
	}

	/**
	 * Creates a resource object from a given hash and filename. The according
	 * resource pointer is fetched automatically.
	 *
	 * @param string $resourceHash
	 * @param string $originalFilename
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	protected function createResourceFromHashAndFilename($resourceHash, $originalFilename) {
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($originalFilename);

		$resourcePointer = $this->getResourcePointerForHash($resourceHash);
		$resource->setResourcePointer($resourcePointer);

		return $resource;
	}

	/**
	 * Attaches the given resource to the imported resources of this script run
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 */
	protected function attachImportedResource(\TYPO3\Flow\Resource\Resource $resource) {
		$this->importedResources->attach($resource, array(
			'originalFilename' => $resource->getFilename()
		));
	}

	/**
	 * Registers a Stream Wrapper Adapter for the resource:// scheme.
	 *
	 * @return void
	 */
	protected function initializeStreamWrapper() {
		$streamWrapperClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
		foreach ($streamWrapperClassNames as $streamWrapperClassName) {
			$scheme = $streamWrapperClassName::getScheme();
			if (in_array($scheme, stream_get_wrappers())) {
				stream_wrapper_unregister($scheme);
			}
			stream_wrapper_register($scheme, '\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter');
			\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::registerStreamWrapper($scheme, $streamWrapperClassName);
		}
	}

	/**
	 * Initializes storage objects according to the current settings
	 *
	 * @return void
	 */
	protected function initializeStorages() {
		foreach ($this->settings['resource']['storages'] as $storageName => $storageDefinition) {
			if (!isset($storageDefinition['storage'])) {
				throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax and make sure to specify a valid storage class name.', $storageName), 1361467211);
			}
			if (!class_exists($storageDefinition['storage'])) {
				throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has not defined a valid "storage" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $storageName, $storageDefinition['storage']), 1361467212);
			}
			$options = (isset($storageDefinition['storageOptions']) ? $storageDefinition['storageOptions'] : array());
			$this->storages[$storageName] = new $storageDefinition['storage']($storageName, $options);
		}
	}

	/**
	 *
	 *
	 * @return void
	 */
	protected function initializeTargets() {
		foreach ($this->settings['resource']['targets'] as $targetName => $targetDefinition) {
			if (!isset($targetDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $targetName), 1361467838);
			}
			if (!class_exists($targetDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $targetName, $targetDefinition['target']), 1361467839);
			}
			$options = (isset($targetDefinition['targetOptions']) ? $targetDefinition['targetOptions'] : array());
			$this->targets[$targetName] = new $targetDefinition['target']($targetName, $options);
		}
	}

	/**
	 *
	 *
	 * @return void
	 */
	protected function initializeCollections() {
		foreach ($this->settings['resource']['collections'] as $collectionName => $collectionDefinition) {
			if (!isset($collectionDefinition['sources']) || !is_array($collectionDefinition['sources'])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "sources" option. Please check the configuration syntax.', $collectionName), 1361468805);
			}
			if (!isset($collectionDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $collectionName), 1361468923);
			}
			if (!isset($this->targets[$collectionDefinition['target']])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $collectionName, $collectionDefinition['target']), 1361468924);
			}

			$sources = array();
			foreach ($collectionDefinition['sources'] as $index => $sourceDefinition) {
				if (!isset($sourceDefinition['storage'])) {
					throw new Exception(sprintf('The source definition #%s of the resource collection "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax and make sure to specify a valid storage class name.', $index, $collectionName), 1361481030);
				}
				if (!isset($this->storages[$sourceDefinition['storage']])) {
					throw new Exception(sprintf('The source definition #%s of the resource collection "%s" defined in your settings referred to a non-existing storage "%s". Please check the configuration syntax and make sure to specify a valid storage class name.', $index, $collectionName, $sourceDefinition['storage']), 1361481031);
				}
				$source = $sourceDefinition;
				$source['storage'] = $this->storages[$sourceDefinition['storage']];
				$sources[] = $source;
			}

			$this->collections[$collectionName] = new Collection($collectionName, $sources, $this->targets[$collectionDefinition['target']]);
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
