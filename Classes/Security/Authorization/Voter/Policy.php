<?php
namespace TYPO3\FLOW3\Security\Authorization\Voter;

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
 * An access decision voter, that asks the FLOW3 PolicyService for a decision.
 *
 * @scope singleton
 */
class Policy implements \TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface {

	/**
	 * The policy service
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\FLOW3\Security\Policy\PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\TYPO3\FLOW3\Security\Policy\PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * This is the default Policy voter, it votes for the access privilege for the given join point
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current securit context
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 */
	public function voteForJoinPoint(\TYPO3\FLOW3\Security\Context $securityContext, \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($securityContext->getRoles() as $role) {
			try {
				$privileges = $this->policyService->getPrivilegesForJoinPoint($role, $joinPoint);
			} catch (\TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException $e) {
				return self::VOTE_ABSTAIN;
			}

			foreach ($privileges as $privilege) {
				if ($privilege === \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT) {
					$accessGrants++;
				} elseif ($privilege === \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY) {
					$accessDenies++;
				}
			}
		}

		if ($accessDenies > 0) {
			return self::VOTE_DENY;
		}
		if ($accessGrants > 0) {
			return self::VOTE_GRANT;
		}

		return self::VOTE_ABSTAIN;
	}

	/**
	 * This is the default Policy voter, it votes for the access privilege for the given resource
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current securit context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 */
	public function voteForResource(\TYPO3\FLOW3\Security\Context $securityContext, $resource) {
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($securityContext->getRoles() as $role) {
			try {
				$privilege = $this->policyService->getPrivilegeForResource($role, $resource);
			} catch (\TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException $e) {
				return self::VOTE_ABSTAIN;
			}

			if ($privilege === NULL) continue;

			if ($privilege === \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT) {
				$accessGrants++;
			} elseif ($privilege === \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY) {
				$accessDenies++;
			}
		}

		if ($accessDenies > 0) {
			return self::VOTE_DENY;
		}
		if ($accessGrants > 0) {
			return self::VOTE_GRANT;
		}

		return self::VOTE_ABSTAIN;
	}
}

?>