<?php
namespace TYPO3\FLOW3\Package\MetaData;

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
 * Constraint meta data model
 *
 */
abstract class AbstractConstraint {

	/**
	 * One of depends, conflicts or suggests
	 * @var string
	 */
	protected $constraintType;

	/**
	 * The constraint name or value
	 * @var string
	 */
	protected $value;

	/**
	 * The minimum version
	 * @var string
	 */
	protected $minVersion;

	/**
	 * The maximum version
	 * @var string
	 */
	protected $maxVersion;

	/**
	 * Meta data constraint constructor
	 *
	 * @param string $constraintType
	 * @param string $value
	 * @param string $minVersion
	 * @param string $maxVersion
	 */
	public function __construct($constraintType, $value, $minVersion = null, $maxVersion = null) {
		$this->constraintType = $constraintType;
		$this->value = $value;
		$this->minVersion = $minVersion;
		$this->maxVersion = $maxVersion;
	}

	/**
	 * @return string The constraint name or value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return string The minimum version
	 */
	public function getMinVersion() {
		return $this->minVersion;
	}

	/**
	 * @return string The maximum version
	 */
	public function getMaxVersion() {
		return $this->maxVersion;
	}

	/**
	 * @return string The constraint type (depends, conflicts, suggests)
	 */
	public function getConstraintType() {
		return $this->constraintType;
	}

	/**
	 * @return string The constraint scope (package, system)
	 */
	abstract public function getConstraintScope();
}
?>