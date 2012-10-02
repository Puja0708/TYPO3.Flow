<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Validator to chain many validators in a disjunction (logical or). So only one
 * validator has to be valid, to make the whole disjunction valid. Errors are
 * only returned if all validators failed.
 *
 * @api
 */
class DisjunctionValidator extends AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the
	 * disjunction.
	 *
	 * If all validators fail, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function validate($value) {
		$result = new \TYPO3\Flow\Error\Result();

		$oneWithoutErrors = FALSE;
		foreach ($this->validators as $validator) {
			$validatorResult = $validator->validate($value);
			if ($validatorResult->hasErrors()) {
				$result->merge($validatorResult);
			} else {
				$oneWithoutErrors = TRUE;
			}
		}

		if ($oneWithoutErrors === TRUE) {
			$result = new \TYPO3\Flow\Error\Result();
		}
		return $result;
	}
}

?>