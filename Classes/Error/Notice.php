<?php
namespace TYPO3\FLOW3\Error;

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
 */

/**
 * An object representation of a generic notice. Subclass this to create
 * more specific notices if necessary.
 *
 * @api
 * @FLOW3\Scope("prototype")
 */
class Notice extends \TYPO3\FLOW3\Error\Message {

	/**
	 * The severity of this message ('Notice').
	 * @var string
	 */
	protected $severity = self::SEVERITY_NOTICE;

}

?>
