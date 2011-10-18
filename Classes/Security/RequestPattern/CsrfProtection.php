<?php
namespace TYPO3\FLOW3\Security\RequestPattern;

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
 * This class holds a request pattern that decides, if csrf protection was enabled for the current request and searches
 * for invalid csrf protection tokens.
 *
 */
class CsrfProtection implements \TYPO3\FLOW3\Security\RequestPatternInterface {

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 * @FLOW3\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @FLOW3\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 * @FLOW3\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyService
	 * @FLOW3\Inject
	 */
	protected $policyService;

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 */
	public function canMatch(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		if ($request instanceof \TYPO3\FLOW3\MVC\Web\Request) return TRUE;
		return FALSE;
	}

	/**
	 * NULL: This pattern holds no configured pattern value
	 *
	 * @return string The set pattern (always NULL here)
	 */
	public function getPattern() {
		return NULL;
	}

	/**
	 * Does nothing, as this pattern holds not configure pattern value
	 *
	 * @param string $uriPattern Not used
	 * @return void
	 */
	public function setPattern($uriPattern) {}

	/**
	 * Matches a \TYPO3\FLOW3\MVC\RequestInterface against the configured CSRF pattern rules and searches for invalid
	 * csrf tokens.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function matchRequest(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		$controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
		$actionName = $request->getControllerActionName(). 'Action';

		if ($this->policyService->hasPolicyEntryForMethod($controllerClassName, $actionName)
			&& !$this->reflectionService->isMethodTaggedWith($controllerClassName, $actionName, 'skipcsrfprotection')) {
			$internalArguments = $request->getInternalArguments();
			if (!isset($internalArguments['__csrfToken'])) return TRUE;
			$csrfToken = $internalArguments['__csrfToken'];
			if (!$this->securityContext->hasCsrfProtectionTokens()) {
				throw new \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException('No tokens in security context, possible session timeout', 1317309673);
			}
			if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === FALSE) return TRUE;
		}
		return FALSE;
	}
}

?>