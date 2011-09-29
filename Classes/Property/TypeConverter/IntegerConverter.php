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

/**
 * Converter which transforms a simple type to an integer, by simply casting it.
 *
 * @api
 * @scope singleton
 */
class IntegerConverter extends \TYPO3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('integer', 'string');

	/**
	 * @var string
	 */
	protected $targetType = 'integer';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, in fact a noop here.
	 *
	 * @param integer $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return integer
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		return (integer)$source;
	}
}
?>