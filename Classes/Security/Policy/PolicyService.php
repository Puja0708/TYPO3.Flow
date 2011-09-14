<?php
namespace TYPO3\FLOW3\Security\Policy;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The policy service reads the policy configuration. The security adivce asks this service which methods have to be intercepted by a security interceptor.
 * The access decision voters get the roles and privileges configured (in the security policy) for a specific method invocation from this service.
 *
 * @scope singleton
 */
class PolicyService implements \TYPO3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	const
		PRIVILEGE_ABSTAIN = 0,
		PRIVILEGE_GRANT = 1,
		PRIVILEGE_DENY = 2;

	/**
	 * The FLOW3 settings
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $policy = array();

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyExpressionParser
	 */
	protected $policyExpressionParser;

	/**
	 * All configured resources
	 * @var array
	 */
	protected $resources = array();

	/**
	 * Array of pointcut filters used to match against the configured policy.
	 * @var array
	 */
	public $filters = array();

	/**
	 * A multidimensional array used containing the roles and privileges for each intercepted method
	 * @var array
	 */
	public $acls = array();

	/**
	 * The constraints for entity resources
	 * @var array
	 */
	protected $entityResourcesConstraints = array();

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager The configuration manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectConfigurationManager(\TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the Cache Manager because we cannot inject an automatically factored cache during compile time.
	 *
	 * @param \TYPO3\FLOW3\Cache\CacheManager $cacheManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCacheManager(\TYPO3\FLOW3\Cache\CacheManager $cacheManager) {
		$this->cache = $cacheManager->getCache('FLOW3_Security_Policy');
	}

	/**
	 * Injects the policy expression parser
	 *
	 * @param \TYPO3\FLOW3\Security\Policy\PolicyExpressionParser $parser
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPolicyExpressionParser(\TYPO3\FLOW3\Security\Policy\PolicyExpressionParser $parser) {
		$this->policyExpressionParser = $parser;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Initializes this Policy Service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->policy = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY);

		$this->setAclsForEverybodyRole();

		if ($this->cache->has('acls')) {
			$this->acls = $this->cache->get('acls');
		} else {
			$this->parseEntityAcls();
		}

		if ($this->cache->has('entityResourcesConstraints')) {
			$this->entityResourcesConstraints = $this->cache->get('entityResourcesConstraints');
		} else {
			if (array_key_exists('resources', $this->policy) && array_key_exists('entities', $this->policy['resources'])) {
				$this->entityResourcesConstraints = $this->policyExpressionParser->parseEntityResources($this->policy['resources']['entities']);
			}
		}
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 * This method also creates a cache entry for every method, to cache the associated roles and privileges.
	 *
	 * @param string $className Name of the class to check the name of
	 * @param string $methodName Name of the method to check the name of
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->settings['security']['enable'] === FALSE) {
			return FALSE;
		}

		$matches = FALSE;

		if ($this->filters === array()) {
			if (isset($this->policy['resources']['methods']) === FALSE) {
				return FALSE;
			}

			foreach ($this->policy['acls'] as $role => $acl) {
				if (!isset($acl['methods'])) {
					continue;
				}
				if (!is_array($acl['methods'])) {
					throw new \TYPO3\FLOW3\Security\Exception\MissingConfigurationException('The configuration for role "' . $role . '" on method resources is not correctly defined. Make sure to use the correct syntax in the Policy.yaml files.', 1277383564);
				}

				foreach ($acl['methods'] as $resource => $privilege) {
					$resourceTrace = array();
					$this->filters[$role][$resource] = $this->policyExpressionParser->parseMethodResources($resource, $this->policy['resources']['methods'], $resourceTrace);

					foreach ($resourceTrace as $currentResource) {
						$policyForResource = array();
						switch ($privilege) {
							case 'GRANT':
								$policyForResource['privilege'] = self::PRIVILEGE_GRANT;
								break;
							case 'DENY':
								$policyForResource['privilege'] = self::PRIVILEGE_DENY;
								break;
							case 'ABSTAIN':
								$policyForResource['privilege'] = self::PRIVILEGE_ABSTAIN;
								break;
							default:
								throw new \TYPO3\FLOW3\Security\Exception\InvalidPrivilegeException('Invalid privilege defined in security policy. An ACL entry may have only one of the privileges ABSTAIN, GRANT or DENY, but we got:' . $privilege . ' for role : ' . $role . ' and resource: ' . $resource, 1267311437);
						}

						if ($this->filters[$role][$resource]->hasRuntimeEvaluationsDefinition() === TRUE) {
							$policyForResource['runtimeEvaluationsClosureCode'] = $this->filters[$role][$resource]->getRuntimeEvaluationsClosureCode();
						} else {
							$policyForResource['runtimeEvaluationsClosureCode'] = FALSE;
						}

						$this->acls[$currentResource][$role] = $policyForResource;
					}
				}
			}
		}

		foreach ($this->filters as $role => $filtersForRole) {
			foreach ($filtersForRole as $resource => $filter) {
				if ($filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
					$matches = TRUE;
					$methodIdentifier = strtolower($className . '->' . $methodName);

					$policyForJoinPoint = array();
					switch ($this->policy['acls'][$role]['methods'][$resource]) {
						case 'GRANT':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_GRANT;
							break;
						case 'DENY':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_DENY;
							break;
						case 'ABSTAIN':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_ABSTAIN;
							break;
						default:
							\TYPO3\FLOW3\Security\Exception\InvalidPrivilegeException('Invalid privilege defined in security policy. An ACL entry may have only one of the privileges ABSTAIN, GRANT or DENY, but we got:' . $this->policy['acls'][$role]['methods'][$resource] . ' for role : ' . $role . ' and resource: ' . $resource, 1267308533);
					}

					if ($filter->hasRuntimeEvaluationsDefinition() === TRUE) {
						$policyForJoinPoint['runtimeEvaluationsClosureCode'] = $filter->getRuntimeEvaluationsClosureCode();
					} else {
						$policyForJoinPoint['runtimeEvaluationsClosureCode'] = FALSE;
					}

					$this->acls[$methodIdentifier][$role][$resource] = $policyForJoinPoint;
				}
			}
		}

		return $matches;
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * Returns an array of all configured roles
	 *
	 * @return array Array of all configured roles
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles() {
		$roles = array();
		foreach ($this->policy['roles'] as $roleIdentifier => $parentRoles) {
			$roles[] = new \TYPO3\FLOW3\Security\Policy\Role($roleIdentifier);
		}
		return $roles;
	}

	/**
	 * Returns all parent roles for the given role, that are configured in the policy.
	 *
	 * @param \TYPO3\FLOW3\Security\Policy\Role $role The role to get the parents for
	 * @return array<TYPO3\Security\Policy\Role> Array of parent roles
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAllParentRoles(\TYPO3\FLOW3\Security\Policy\Role $role) {
		$result = array();

		foreach ($this->policy['roles'][(string)$role] as $currentIdentifier) {
			$currentParent = new \TYPO3\FLOW3\Security\Policy\Role($currentIdentifier);
			if (!in_array($currentParent, $result)) $result[] = $currentParent;
			foreach ($this->getAllParentRoles($currentParent) as $currentGrandParent) {
				if (!in_array($currentGrandParent, $result)) $result[] = $currentGrandParent;
			}
		}

		return $result;
	}

	/**
	 * Returns the configured roles for the given joinpoint
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of roles
	 * @throws \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesForJoinPoint(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());
		if (!isset($this->acls[$methodIdentifier])) throw new \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222084767);

		$roles = array();
		foreach (array_keys($this->acls[$methodIdentifier]) as $roleIdentifier) {
			$roles[] = new \TYPO3\FLOW3\Security\Policy\Role($roleIdentifier);
		}

		return $roles;
	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint. The returned array
	 * contains the privilege's resource as key of each privilege.
	 *
	 * @param \TYPO3\FLOW3\Security\Policy\Role $role The role for which the privileges should be returned
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint for which the privileges should be returned
	 * @return array Array of privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @throws \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException
	 */
	public function getPrivilegesForJoinPoint(\TYPO3\FLOW3\Security\Policy\Role $role, \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());
		$roleIdentifier = (string)$role;

		if (!isset($this->acls[$methodIdentifier])) throw new \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222100851);
		if (!isset($this->acls[$methodIdentifier][$roleIdentifier])) return array();

		$privileges = array();
		foreach ($this->acls[$methodIdentifier][$roleIdentifier] as $resource => $privilegeConfiguration) {
			if ($privilegeConfiguration['runtimeEvaluationsClosureCode'] !== FALSE) {
					// Make object manager usable as closure variable
				$objectManager = $this->objectManager;
				eval('$runtimeEvaluator = ' . $privilegeConfiguration['runtimeEvaluationsClosureCode'] . ';');
				if ($runtimeEvaluator->__invoke($joinPoint) === FALSE) continue;
			}

			$privileges[$resource] = $privilegeConfiguration['privilege'];
		}

		return $privileges;
	}

	/**
	 * Returns the privilege a specific role has for the given resource.
	 * Note: Resources with runtime evaluations return always a PRIVILEGE_DENY!
	 * @see getPrivilegesForJoinPoint() instead, if you need privileges for them.
	 *
	 * @param \TYPO3\FLOW3\Security\Policy\Role $role The role for which the privileges should be returned
	 * @param string $resource The resource for which the privileges should be returned
	 * @return integer One of: PRIVILEGE_GRANT, PRIVILEGE_DENY
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @throws \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException
	 */
	public function getPrivilegeForResource(\TYPO3\FLOW3\Security\Policy\Role $role, $resource) {
		if (!isset($this->acls[$resource])) {
			if (isset($this->resources[$resource])) {
				return self::PRIVILEGE_DENY;
			} else {
				throw new \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException('The given resource ("' . $resource . '") was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1248348214);
			}
		}

		$roleIdentifier = (string)$role;
		if (!array_key_exists($roleIdentifier, $this->acls[$resource])) {
			return NULL;
		}

		if ($this->acls[$resource][$roleIdentifier]['runtimeEvaluationsClosureCode'] !== FALSE) {
			return self::PRIVILEGE_DENY;
		}

		return $this->acls[$resource][$roleIdentifier]['privilege'];
	}

	/**
	 * Checks if the given method has a policy entry. If $roles are given
	 * this method returns only TRUE, if there is an acl entry for the method for
	 * at least one of the given roles.
	 *
	 * @param string $className The class name to check the policy for
	 * @param string $methodName The method name to check the policy for
	 * @param array $roles
	 * @return boolean TRUE if the given controller action has a policy entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasPolicyEntryForMethod($className, $methodName, array $roles = array()) {
		$methodIdentifier = strtolower($className . '->' . $methodName);

		if (isset($this->acls[$methodIdentifier])) {
			if (count($roles) > 0) {
				foreach ($roles as $roleIdentifier) {
					if (isset($this->acls[$methodIdentifier][$roleIdentifier])) return TRUE;
				}
			} else {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Checks if the given entity type has a policy entry for at least one of the given roles
	 *
	 * @param string $entityType The entity type (object name) to be checked
     * @param array $roles The roles to be checked
	 * @return boolean TRUE if the given entity type has a policy entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasPolicyEntryForEntityType($entityType, array $roles) {
		$entityType = str_replace('\\', '_', $entityType);

		if (isset($this->entityResourcesConstraints[$entityType])) {
			foreach ($this->entityResourcesConstraints[$entityType] as $resource => $constraint) {
				foreach ($roles as $roleIdentifier) {
					if (isset($this->acls[$resource][(string)$roleIdentifier])) return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Returns an array of resource constraints, which are configured for the given entity type
	 * and for at least one of the given roles.
	 * Note: If two roles have conflicting privileges for the same resource the GRANT priviliege
	 * has precedence.
	 *
	 * @param string $entityType The entity type (object name)
	 * @param array $roles An array of roles the resources have to be configured for
	 * @return array An array resource constraints
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getResourcesConstraintsForEntityTypeAndRoles($entityType, array $roles) {
		$deniedResources = array();
		$grantedResources = array();

		$entityType = str_replace('\\', '_', $entityType);

		foreach ($this->entityResourcesConstraints[$entityType] as $resource => $constraint) {
			foreach ($roles as $roleIdentifier) {
				if (!isset($this->acls[$resource][(string)$roleIdentifier]['privilege'])) continue;

				if ($this->acls[$resource][(string)$roleIdentifier]['privilege'] === self::PRIVILEGE_DENY) {
					$deniedResources[$resource] = $constraint;
				} else {
					$grantedResources[] = $resource;
				}
			}
		}

		foreach ($grantedResources as $grantedResource) {
			if (isset($deniedResources[$grantedResource])) unset($deniedResources[$grantedResource]);
		}

		return $deniedResources;
	}

	/**
	 * Parses the policy and stores the configured entity acls in the internal acls array
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parseEntityAcls() {
		foreach ($this->policy['acls'] as $role => $aclEntries) {
			if (!array_key_exists('entities', $aclEntries)) continue;

			foreach ($aclEntries['entities'] as $resource => $privilege) {
				if (!isset($this->acls[$resource])) $this->acls[$resource] = array();
				$this->acls[$resource][$role] = array(
					'privilege' => ($privilege === 'GRANT' ? self::PRIVILEGE_GRANT : self::PRIVILEGE_DENY)
				);
			}
		}
	}

	/**
	 * Sets the default ACLs for the Everybody role
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function setAclsForEverybodyRole() {
		$this->policy['roles']['Everybody'] = array();

		if (!isset($this->policy['acls']['Everybody'])) $this->policy['acls']['Everybody'] = array();
		if (!isset($this->policy['acls']['Everybody']['methods'])) $this->policy['acls']['Everybody']['methods'] = array();
		if (!isset($this->policy['acls']['Everybody']['entities'])) $this->policy['acls']['Everybody']['entities'] = array();

		foreach (array_keys($this->policy['resources']['methods']) as $resource) {
			if (!isset($this->policy['acls']['Everybody']['methods'][$resource])) $this->policy['acls']['Everybody']['methods'][$resource] = 'ABSTAIN';
		}
		foreach ($this->policy['resources']['entities'] as $resourceDefinition) {
			foreach (array_keys($resourceDefinition) as $resource) {
				if (!isset($this->policy['acls']['Everybody']['entities'][$resource])) $this->policy['acls']['Everybody']['entities'][$resource] = 'ABSTAIN';
			}
		}
	}

	/**
	 * Save the found matches to the cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function savePolicyCache() {
		$tags = array('TYPO3_FLOW3_AOP');
		if (!$this->cache->has('acls')) {
			$this->cache->set('acls', $this->acls, $tags);
		}
		if (!$this->cache->has('entityResourcesConstraints')) {
			$this->cache->set('entityResourcesConstraints', $this->entityResourcesConstraints);
		}
	}
}

?>