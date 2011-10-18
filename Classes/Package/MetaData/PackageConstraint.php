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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Package constraint meta model
 *
 * @FLOW3\Scope("prototype")
 */
class PackageConstraint extends \TYPO3\FLOW3\Package\MetaData\AbstractConstraint {

	/**
	 * @return string The constraint scope
	 * @see \TYPO3\FLOW3\Package\MetaData\Constraint::getConstraintScope()
	 */
	public function getConstraintScope() {
		return \TYPO3\FLOW3\Package\MetaDataInterface::CONSTRAINT_SCOPE_PACKAGE;
	}
}
?>