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
 * A generic collection validator
 *
 * @api
 */
class CollectionValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * @var \TYPO3\FLOW3\Validation\ValidatorResolver
	 * @FLOW3\Inject
	 */
	protected $validatorResolver;

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @param mixed $value A collection to be validated
	 * @return void
	 */
	protected function isValid($value) {
		if ($value instanceof \Doctrine\ORM\PersistentCollection && !$value->isInitialized()) {
			return;
		}

		if ((is_object($value) && !\TYPO3\FLOW3\Utility\TypeHandling::isCollectionType(get_class($value))) && !is_array($value)) {
			$this->addError('The given subject was not a collection.', 1317204797);
			return;
		}

		foreach ($value as $index => $collectionElement) {
			if (isset($this->options['elementValidator'])) {
				$collectionElementValidator = $this->validatorResolver->createValidator($this->options['elementValidator']);
			} elseif (isset($this->options['elementType'])) {
				if (isset($this->options['validationGroups'])) {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType'], $this->options['validationGroups']);
				} else {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType']);
				}
			} else {
				return;
			}
			$this->result->forProperty($index)->merge($collectionElementValidator->validate($collectionElement));
		}
	}
}

?>