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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Abstract validator
 *
 * @api
 * @FLOW3\Scope("prototype")
 */
abstract class AbstractValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \TYPO3\FLOW3\Error\Result
	 */
	protected $result;

	/**
	 * Sets options for the validator
	 *
	 * @param array $validationOptions Options for the validator
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct(array $validationOptions = array()) {
		$this->options = $validationOptions;
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occured.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\FLOW3\Error\Result();
		$this->isValid($value);
		return $this->result;
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @return void
	 */
	abstract protected function isValid($value);

	/**
	 * Creates a new validation error object and adds it to $this->errors
	 *
	 * @param string $message The error message
	 * @param integer $code The error code (a unix timestamp)
	 * @param array $arguments Arguments to be replaced in message
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	protected function addError($message, $code, array $arguments = array()) {
		$this->result->addError(new \TYPO3\FLOW3\Validation\Error($message, $code, $arguments));
	}
}

?>