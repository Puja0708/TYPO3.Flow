<?php
namespace TYPO3\FLOW3\Security;

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
 * The repository for accounts
 *
 * @scope singleton
 */
class AccountRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\FLOW3\Security\Account';

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('creationDate' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING);

	/**
	 * Returns the account for a specific authentication provider with the given identitifer
	 *
	 * @param string $accountIdentifier The account identifier
	 * @param string $authenticationProviderName The authentication provider name
	 * @return \TYPO3\FLOW3\Security\Account
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function findByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('accountIdentifier', $accountIdentifier),
				$query->equals('authenticationProviderName', $authenticationProviderName)
			)
		)->execute()->getFirst();
	}

	/**
	 * Returns the account for a specific authentication provider with the given identitifer if it's not expired
	 *
	 * @param string $accountIdentifier The account identifier
	 * @param string $authenticationProviderName The authentication provider name
	 * @return \TYPO3\FLOW3\Security\Account
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findActiveByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('accountIdentifier', $accountIdentifier),
				$query->equals('authenticationProviderName', $authenticationProviderName),
				$query->logicalOr(
					$query->equals('expirationDate', NULL),
					$query->greaterThan('expirationDate', new \DateTime('today'))
				)
			)
		)->execute()->getFirst();
	}
}

?>
