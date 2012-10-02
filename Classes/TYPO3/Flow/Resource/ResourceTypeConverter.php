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
 * An type converter for ResourcePointer objects
 *
 * @Flow\Scope("singleton")
 */
class ResourceTypeConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Flow\Resource\Resource';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

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
	protected $convertedResources = array();

	/**
	 * Converts the given string or array to a ResourcePointer object.
	 *
	 * If the input format is an array, this method assumes the resource to be a
	 * fresh file upload and imports the temporary upload file through the
	 * resource manager.
	 *
	 * @param array $source The upload info (expected keys: error, name, tmp_name)
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Flow\Resource\Resource|TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (!isset($source['error']) || $source['error'] === \UPLOAD_ERR_NO_FILE) {
			if (isset($source['submittedFile']) && isset($source['submittedFile']['filename']) && isset($source['submittedFile']['resourcePointer'])) {
				$resourcePointer = $this->persistenceManager->getObjectByIdentifier($source['submittedFile']['resourcePointer'], 'TYPO3\Flow\Resource\ResourcePointer');
				if ($resourcePointer) {
					$resource = new Resource();
					$resource->setFilename($source['submittedFile']['filename']);
					$resource->setResourcePointer($resourcePointer);
					return $resource;
				}
			}
			return NULL;
		}

		if ($source['error'] !== \UPLOAD_ERR_OK) {
			switch ($source['error']) {
				case \UPLOAD_ERR_INI_SIZE:
				case \UPLOAD_ERR_FORM_SIZE:
				case \UPLOAD_ERR_PARTIAL:
					return new \TYPO3\Flow\Error\Error(\TYPO3\Flow\Utility\Files::getUploadErrorMessage($source['error']) , 1264440823);
				default:
					$this->systemLogger->log(sprintf('A server error occurred while converting an uploaded resource: "%s"', \TYPO3\Flow\Utility\Files::getUploadErrorMessage($source['error'])), LOG_ERR);
					return new \TYPO3\Flow\Error\Error('An error occurred while uploading. Please try again or contact the administrator if the problem remains' , 1340193849);
			}
		}

		if (isset($this->convertedResources[$source['tmp_name']])) {
			return $this->convertedResources[$source['tmp_name']];
		}

		$resource = $this->resourceManager->importUploadedResource($source);
		if ($resource === FALSE) {
			return new \TYPO3\Flow\Error\Error('The resource manager could not create a Resource instance.' , 1264517906);
		} else {
			$this->convertedResources[$source['tmp_name']] = $resource;
			return $resource;
		}
	}
}

?>