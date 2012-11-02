<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
 * This converter transforms arrays to simple objects (POPO) by setting properties.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ObjectConverter extends AbstractTypeConverter {

	/**
	 * @var integer
	 */
	const CONFIGURATION_TARGET_TYPE = 3;

	/**
	 * @var integer
	 */
	const CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED = 4;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'object';

	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Only convert non-persistent types
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		return !(
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'Doctrine\ORM\Mapping\Entity')
		);
	}

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (isset($source['__type'])) {
			unset($source['__type']);
		}
		return $source;
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		if ($this->reflectionService->hasMethod($targetType, \TYPO3\Flow\Reflection\ObjectAccess::buildSetterMethodName($propertyName))) {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, \TYPO3\Flow\Reflection\ObjectAccess::buildSetterMethodName($propertyName));
			$methodParameter = current($methodParameters);
			if (!isset($methodParameter['type'])) {
				throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
			} else {
				return $methodParameter['type'];
			}
		} else {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, '__construct');
			if (isset($methodParameters[$propertyName]) && isset($methodParameters[$propertyName]['type'])) {
				return $methodParameters[$propertyName]['type'];
			} else {
				throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" had no setter or constructor argument in target object of type "' . $targetType . '".', 1303379126);
			}
		}
	}

	/**
	 * Convert an object from $source to an object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidDataTypeException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$effectiveTargetType = $targetType;
		if (isset($source['__type'])) {
			if ($configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== TRUE) {
				throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317051258);
			}
			$effectiveTargetType = $source['__type'];
		}
		$object = $this->buildObject($convertedChildProperties, $effectiveTargetType);
		if ($effectiveTargetType !== $targetType && !$object instanceof $targetType) {
			throw new \TYPO3\Flow\Property\Exception\InvalidDataTypeException('The given type "' . $source['__type'] . '" is not a subtype of "' . $targetType .'"', 1317051266);
		}

		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = \TYPO3\Flow\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				$exceptionMessage = sprintf(
					'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
					$propertyName,
					(is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
					$targetType
				);
				throw new \TYPO3\Flow\Property\Exception\InvalidTargetException($exceptionMessage, 1304538165);
			}
		}

		return $object;
	}

	/**
	 * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues.
	 * If constructor argument values are missing from the given array the method looks for a
	 * default value in the constructor signature.
	 *
	 * Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
	 *
	 * @param array &$possibleConstructorArgumentValues
	 * @param string $objectType
	 * @return object The created instance
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException if a required constructor argument is missing
	 */
	protected function buildObject(array &$possibleConstructorArgumentValues, $objectType) {
		$className = $this->objectManager->getClassNameByObjectName($objectType);
		if ($this->reflectionService->hasMethod($className, '__construct')) {
			$constructorSignature = $this->reflectionService->getMethodParameters($className, '__construct');
			$constructorArguments = array();
			foreach ($constructorSignature as $constructorArgumentName => $constructorArgumentInformation) {
				if (array_key_exists($constructorArgumentName, $possibleConstructorArgumentValues)) {
					$constructorArguments[] = $possibleConstructorArgumentValues[$constructorArgumentName];
					unset($possibleConstructorArgumentValues[$constructorArgumentName]);
				} elseif ($constructorArgumentInformation['optional'] === TRUE) {
					$constructorArguments[] = $constructorArgumentInformation['defaultValue'];
				} else {
					throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for object of type "' . $objectType . '".' , 1268734872);
				}
			}
			$classReflection = new \ReflectionClass($className);
			return $classReflection->newInstanceArgs($constructorArguments);
		} else {
			return new $className();
		}
	}

}
?>