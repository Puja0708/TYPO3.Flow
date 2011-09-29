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
 * Contract for a validator
 *
 * @author Robert Lemke <robert@typo3.org>
 * @api
 */
interface ValidatorInterface {

	/**
	 * Sets validation options for the validator
	 *
	 * @param array $validationOptions The validation options
	 * @api
	 */
	//public function __construct(array $validationOptions = array());

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occured.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($value);

}
?>