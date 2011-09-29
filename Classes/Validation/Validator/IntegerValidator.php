<?php
namespace TYPO3\FLOW3\Validation\Validator;

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
 * Validator for integers
 *
 * @api
 * @scope singleton
 */
class IntegerValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Checks if the given value is a valid integer.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @param \TYPO3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function isValid($value) {
		if (filter_var($value, FILTER_VALIDATE_INT) !== FALSE) return;
		$this->addError('A valid integer number is expected.', 1221560494);
	}
}

?>