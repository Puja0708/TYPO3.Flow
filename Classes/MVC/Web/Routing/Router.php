<?php
namespace TYPO3\FLOW3\MVC\Web\Routing;

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
 * The default web router
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class Router implements \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface {

	/**
	 * @var string
	 */
	protected $controllerObjectNamePattern = '@package\@subpackage\Controller\@controllerController';

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Array containing the configuration for all routes.
	 * @var array
	 */
	protected $routesConfiguration = array();

	/**
	 * Array of routes to match against
	 * @var array
	 */
	protected $routes = array();

	/**
	 * TRUE if route object have been created, otherwise FALSE
	 * @var boolean
	 */
	protected $routesCreated = FALSE;

	/**
	 * The current request. Will be set in route()
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the environment
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Sets the routes configuration.
	 *
	 * @param array $routesConfiguration The routes configuration
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRoutesConfiguration(array $routesConfiguration) {
		$this->routesConfiguration = $routesConfiguration;
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Request $request The web request to be analyzed. Will be modified by the router.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function route(\TYPO3\FLOW3\MVC\Web\Request $request) {
		$this->request = $request;
		$routePath = $this->request->getRoutePath();
		$matchResults = $this->findMatchResults($routePath);
		if ($matchResults !== NULL) {
			foreach ($matchResults as $argumentName => $argumentValue) {
				$this->request->setArgument($argumentName, $argumentValue);
			}
		}
		$this->setDefaultControllerAndActionNameIfNoneSpecified();
	}

	/**
	 * Returns a list of configured routes
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRoutes() {
		$this->createRoutesFromConfiguration();
		return $this->routes;
	}

	/**
	 * Set the default controller and action names if none has been specified.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setDefaultControllerAndActionNameIfNoneSpecified() {
		if ($this->request->getControllerName() === NULL) {
			$this->request->setControllerName('Standard');
		}
		if ($this->request->getControllerActionName() === NULL) {
			$this->request->setControllerActionName('index');
		}
	}

	/**
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param string $routePath The route path
	 * @return array results of the matching route
	 * @see route()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findMatchResults($routePath) {
		$this->createRoutesFromConfiguration();

		foreach ($this->routes as $route) {
			if ($route->matches($routePath) === TRUE) {
				$matchResults = $route->getMatchResults();
				$this->systemLogger->log('Router route(): Route "' . $route->getName() . '" matched the path "' . $routePath . '".', LOG_DEBUG);
				return $matchResults;
			}
		}
		$this->systemLogger->log('Router route(): No route matched the route path "' . $routePath . '".', LOG_NOTICE);
		return NULL;
	}

	/**
	 * Builds the corresponding uri (excluding protocol and host) by iterating
	 * through all configured routes and calling their respective resolves()
	 * method. If no matching route is found, an empty string is returned.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolve(array $routeValues) {
		$this->createRoutesFromConfiguration();

		foreach ($this->routes as $route) {
			if ($route->resolves($routeValues)) {
				return $route->getMatchingUri();
			}
		}
		$this->systemLogger->log('Router resolve(): Could not resolve a route for building an URI for the given route values.', LOG_WARNING, $routeValues);
		throw new \TYPO3\FLOW3\MVC\Exception\NoMatchingRouteException('Could not resolve a route and its corresponding URI for the given parameters. This may be due to referring to a not existing package / controller / action while building a link or URI. Refer to log and check the backtrace for more details.', 1301610453);
	}

	/**
	 * Creates TYPO3\FLOW3\MVC\Web\Routing\Route objects from the injected routes
	 * configuration.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createRoutesFromConfiguration() {
		if ($this->routesCreated === FALSE) {
			$this->routes = array();
			foreach ($this->routesConfiguration as $routeConfiguration) {
				$route = $this->objectManager->create('TYPO3\FLOW3\MVC\Web\Routing\Route');
				if (isset($routeConfiguration['name'])) {
					$route->setName($routeConfiguration['name']);
				}
				$route->setUriPattern($routeConfiguration['uriPattern']);
				if (isset($routeConfiguration['defaults'])) {
					$route->setDefaults($routeConfiguration['defaults']);
				}
				if (isset($routeConfiguration['routeParts'])) {
					$route->setRoutePartsConfiguration($routeConfiguration['routeParts']);
				}
				if (isset($routeConfiguration['toLowerCase'])) {
					$route->setLowerCase($routeConfiguration['toLowerCase']);
				}
				$this->routes[] = $route;
			}
			$this->routesCreated = TRUE;
		}
	}

	/**
	 * Returns the object name of the controller defined by the package, subpackage key and
	 * controller name
	 *
	 * @param string $packageKey the package key of the controller
	 * @param string $subPackageKey the subpackage key of the controller
	 * @param string $controllerName the controller name excluding the "Controller" suffix
	 * @return string The controller's Object Name or NULL if the controller does not exist
	 * @api
	 */
	public function getControllerObjectName($packageKey, $subpackageKey, $controllerName) {
		$possibleObjectName = $this->controllerObjectNamePattern;
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $subpackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
		return ($controllerObjectName !== FALSE) ? $controllerObjectName : NULL;
	}
}
?>
