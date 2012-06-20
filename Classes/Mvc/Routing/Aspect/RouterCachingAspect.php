<?php
namespace TYPO3\FLOW3\Mvc\Routing\Aspect;

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
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @FLOW3\Aspect
 * @FLOW3\Scope("singleton")
 */
class RouterCachingAspect {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 * @FLOW3\Inject
	 */
	protected $findMatchResultsCache;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\StringFrontend
	 * @FLOW3\Inject
	 */
	protected $resolveCache;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 * @FLOW3\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 * @FLOW3\Inject
	 */
	protected $systemLogger;

	/**
	 * Around advice
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Mvc\Routing\Router->findMatchResults())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function cacheMatchingCall(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$routePath = $joinPoint->getMethodArgument('routePath');

		$cacheIdentifier = md5($routePath);
		if ($this->findMatchResultsCache->has($cacheIdentifier)) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $cacheIdentifier, $routePath), LOG_DEBUG);
			return $this->findMatchResultsCache->get($cacheIdentifier);
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$matchedRoute = $joinPoint->getProxy()->getLastMatchedRoute();
		if ($matchedRoute !== NULL) {
			$this->systemLogger->log(sprintf('Router route(): Route "%s" matched the path "%s".', $matchedRoute->getName(), $routePath), LOG_DEBUG);
		} else {
			$this->systemLogger->log(sprintf('Router route(): No route matched the route path "%s".', $routePath), LOG_NOTICE);
		}
		if ($matchResults !== NULL && $this->containsObject($matchResults) === FALSE) {
			$this->findMatchResultsCache->set($cacheIdentifier, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Mvc\Routing\Router->resolve())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return string Result of the target method
	 */
	public function cacheResolveCall(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$cacheIdentifier = NULL;
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		try {
			$routeValues = $this->convertObjectsToHashes($routeValues);
			\TYPO3\FLOW3\Utility\Arrays::sortKeysRecursively($routeValues);
			$cacheIdentifier = md5(http_build_query($routeValues));
			if ($this->resolveCache->has($cacheIdentifier)) {
				return $this->resolveCache->get($cacheIdentifier);
			}
		} catch (\InvalidArgumentException $exception) {
		}

		$matchingUri = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUri !== NULL && $cacheIdentifier !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $matchingUri);
		}
		return $matchingUri;
	}

	/**
	 * Flushes 'findMatchResults' and 'resolve' caches.
	 *
	 * @return void
	 */
	public function flushCaches() {
		$this->findMatchResultsCache->flush();
		$this->resolveCache->flush();
	}

	/**
	 * Checks if the given subject contains an object
	 *
	 * @param mixed $subject
	 * @return boolean If it contains an object or not
	 */
	protected function containsObject($subject) {
		if (is_object($subject)) {
			return TRUE;
		}
		if (!is_array($subject)) {
			return FALSE;
		}
		foreach ($subject as $value) {
			if ($this->containsObject($value)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Recursively converts objects in an array to their identifiers
	 *
	 * @param array $routeValues the array to be processed
	 * @return array the modified array
	 * @throws \InvalidArgumentException if $routeValues contain an object and its identifier could not be determined
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				$identifier = $this->persistenceManager->getIdentifierByObject($value);
				if ($identifier === NULL) {
					throw new \InvalidArgumentException(sprintf('The identifier of an object of type "%s" could not be determined', get_class($value)), 1340102526);
				}
				$value = $identifier;
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
			}
		}
		return $routeValues;
	}
}
?>