<?php
namespace TYPO3\FLOW3\Validation;

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
 * This object holds a validation error.
 *
 * @scope prototype
 */
class Error extends \TYPO3\FLOW3\Error\Error {

	/**
	 * @var string
	 */
	protected $message = 'Unknown validation error';

	/**
	 * @var string
	 */
	protected $code = 1201447005;
}

?>