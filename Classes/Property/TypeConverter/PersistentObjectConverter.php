<?php
namespace TYPO3\FLOW3\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * This converter transforms arrays or strings to persistent objects. It does the following:
 *
 * - If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
 * - If the input is array, we check if it has an identity property.
 *
 * - If the input has an identity property and NO additional properties, we fetch the object from persistence.
 * - If the input has an identity property AND additional properties, we fetch the object from persistence,
 *   and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
 * - If the input has NO identity property, but additional properties, we create a new object and return it.
 *   However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class PersistentObjectConverter extends ObjectConverter {

	/**
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

	/**
	 * @var integer
	 */
	const CONFIGURATION_MODIFICATION_ALLOWED = 1;

	/**
	 * @var integer
	 */
	const CONFIGURATION_CREATION_ALLOWED = 2;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @param \TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * We can only convert if the $targetType is either tagged with entity or value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		return (
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\FLOW3\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\FLOW3\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'Doctrine\ORM\Mapping\Entity')
		);
	}

	/**
	 * All properties in the source array except __identity are sub-properties.
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_string($source)) {
			return array();
		}
		if (isset($source['__identity'])) {
			unset($source['__identity']);
		}
		return parent::getSourceChildPropertiesToBeConverted($source);
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\FLOW3\Property\Exception\InvalidTargetException
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		$schema = $this->reflectionService->getClassSchema($targetType);
		if (!$schema->hasProperty($propertyName)) {
			throw new \TYPO3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $targetType . '".', 1297978366);
		}
		$propertyInformation = $schema->getProperty($propertyName);
		return $propertyInformation['type'] . ($propertyInformation['elementType']!==NULL ? '<' . $propertyInformation['elementType'] . '>' : '');
	}

	/**
	 * Convert an object from $source to an entity or a value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws \TYPO3\FLOW3\Property\Exception\InvalidTargetException
	 * @throws \InvalidArgumentException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_array($source)) {
			if ($this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\FLOW3\Annotations\ValueObject')) {
				// Unset identity for valueobject to use constructor mapping, since the identity is determined from
				// constructor arguments
				unset($source['__identity']);
			}
			$object = $this->handleArrayData($source, $targetType, $convertedChildProperties, $configuration);
		} elseif (is_string($source)) {
			if ($source === '') {
				return NULL;
			}
			$object = $this->fetchObjectFromPersistence($source, $targetType);
		} else {
			throw new \InvalidArgumentException('Only strings and arrays are accepted.', 1305630314);
		}
		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = \TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				throw new \TYPO3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" having a value of type "' . (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)) . '" could not be set in target object of type "' . $targetType . '".', 1297935345);
			}
		}

		return $object;
	}

	/**
	 * Handle the case if $source is an array.
	 *
	 * @param array $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object
	 * @throws \TYPO3\FLOW3\Property\Exception\InvalidDataTypeException
	 * @throws \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function handleArrayData(array $source, $targetType, array &$convertedChildProperties, \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$effectiveTargetType = $targetType;
		if (isset($source['__type'])) {
			if ($configuration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== TRUE) {
				throw new \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317050430);
			}
			$effectiveTargetType = $source['__type'];
		}
		if (isset($source['__identity'])) {
			$object = $this->fetchObjectFromPersistence($source['__identity'], $effectiveTargetType);

			if (count($source) > 1 && ($configuration === NULL || $configuration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_MODIFICATION_ALLOWED) !== TRUE)) {
				throw new \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
			}
		} else {
			if ($configuration === NULL || $configuration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_CREATION_ALLOWED) !== TRUE) {
				throw new \TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException('Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
			}
			$object = $this->buildObject($convertedChildProperties, $effectiveTargetType);
		}
		if ($effectiveTargetType !== $targetType && !$object instanceof $targetType) {
			throw new \TYPO3\FLOW3\Property\Exception\InvalidDataTypeException('The given type "' . $effectiveTargetType . '" is not a subtype of "' . $targetType .'"', 1317048056);
		}
		return $object;
	}

	/**
	 * Fetch an object from persistence layer.
	 *
	 * @param mixed $identity
	 * @param string $targetType
	 * @return object
	 * @throws \TYPO3\FLOW3\Property\Exception\TargetNotFoundException
	 * @throws \TYPO3\FLOW3\Property\Exception\InvalidSourceException
	 */
	protected function fetchObjectFromPersistence($identity, $targetType) {
		if (is_string($identity)) {
			$object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
		} elseif (is_array($identity)) {
			$object = $this->findObjectByIdentityProperties($identity, $targetType);
		} else {
			throw new \TYPO3\FLOW3\Property\Exception\InvalidSourceException('The identity property "' . $identity . '" is neither a string nor an array.', 1297931020);
		}

		if ($object === NULL) {
			throw new \TYPO3\FLOW3\Property\Exception\TargetNotFoundException('Object with identity "' . print_r($identity, TRUE) . '" not found.', 1297933823);
		}

		return $object;
	}

	/**
	 * Finds an object from the repository by searching for its identity properties.
	 *
	 * @param array $identityProperties Property names and values to search for
	 * @param string $type The object type to look for
	 * @return object Either the object matching the identity or NULL if no object was found
	 * @throws \TYPO3\FLOW3\Property\Exception\DuplicateObjectException if more than one object was found
	 */
	protected function findObjectByIdentityProperties(array $identityProperties, $type) {
		$query = $this->persistenceManager->createQueryForType($type);
		$classSchema = $this->reflectionService->getClassSchema($type);

		$equals = array();
		foreach ($classSchema->getIdentityProperties() as $propertyName => $propertyType) {
			if (isset($identityProperties[$propertyName])) {
				if ($propertyType === 'string') {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName], FALSE);
				} else {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName]);
				}
			}
		}

		if (count($equals) === 1) {
			$constraint = current($equals);
		} else {
			$constraint = $query->logicalAnd(current($equals), next($equals));
			while (($equal = next($equals)) !== FALSE) {
				$constraint = $query->logicalAnd($constraint, $equal);
			}
		}

		$objects = $query->matching($constraint)->execute();
		$numberOfResults = $objects->count();
		if ($numberOfResults === 1) {
			return $objects->getFirst();
		} elseif ($numberOfResults === 0) {
			return NULL;
		} else {
			throw new \TYPO3\FLOW3\Property\Exception\DuplicateObjectException('More than one object was returned for the given identity, this is a constraint violation.', 1259612399);
		}
	}
}
?>