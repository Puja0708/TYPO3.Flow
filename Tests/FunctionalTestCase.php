<?php
namespace TYPO3\FLOW3\Tests;

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
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Core\Bootstrap;
use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\Routing\Route;

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @api
 */
abstract class FunctionalTestCase extends \TYPO3\FLOW3\Tests\BaseTestCase {

	/**
	 * A functional instance of the Object Manager, for use in concrete test cases.
	 *
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @api
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 * @api
	 */
	protected static $bootstrap;

	/**
	 * If enabled, this test case will modify the behavior of the security framework
	 * in a way which allows for easy simulation of roles and authentication.
	 *
	 * Note: this will implicitly enable testable HTTP as well.
	 *
	 * @var boolean
	 * @api
	 */
	protected $testableSecurityEnabled = FALSE;

	/**
	 * If enabled, this test case will automatically provide a virtual browser
	 * for sending HTTP requests to FLOW3's request handler and MVC framework.
	 *
	 * Note: testable security will implicitly enable this as well.
	 *
	 * @var boolean
	 * @api
	 */
	protected $testableHttpEnabled = FALSE;

	/**
	 * If enabled, this test case will automatically run the compile() method on
	 * the Persistence Manager before running a test.
	 *
	 * @var boolean
	 * @api
	 * @todo Check if the remaining behavior related to persistence should also be covered by this setting
	 */
	static protected $testablePersistenceEnabled = FALSE;

	/**
	 * If testableHttpEnabled is set, contains a virtual, preinitialized browser
	 *
	 * @var \TYPO3\FLOW3\Http\Client\Browser
	 * @api
	 */
	protected $browser;

	/**
	 * If testableHttpEnabled is set, contains the router instance used in the browser's request engine
	 *
	 * @var \TYPO3\FLOW3\Mvc\Routing\Router
	 * @api
	 */
	protected $router;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @var \TYPO3\FLOW3\Security\Authentication\Provider\TestingProvider
	 */
	protected $testingProvider;

	/**
	 * Initialize FLOW3
	 *
	 * @return void
	 */
	static public function setUpBeforeClass() {
		self::$bootstrap = \TYPO3\FLOW3\Core\Bootstrap::$staticObjectManager->get('TYPO3\FLOW3\Core\Bootstrap');
	}

	/**
	 * Enables security tests for this testcase
	 *
	 * @return void
	 * @deprecated since 1.1 – please set the class property directly
	 */
	protected function enableTestableSecurity() {
		$this->testableSecurityEnabled = TRUE;
	}

	/**
	 * Sets up test requirements depending on the enabled tests.
	 *
	 * If you override this method, don't forget to call parent::setUp() in your
	 * own implementation.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->objectManager = self::$bootstrap->getObjectManager();

		$session = $this->objectManager->get('TYPO3\FLOW3\Session\SessionInterface');
		if ($session->isStarted()) {
			$session->destroy(sprintf('assure that session is fresh, in setUp() method of functional test %s.', get_class($this) . '::' . $this->getName()));
		}

		if (static::$testablePersistenceEnabled === TRUE) {
			self::$bootstrap->getObjectManager()->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface')->initialize();
			if (is_callable(array(self::$bootstrap->getObjectManager()->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface'), 'compile'))) {
				$result = self::$bootstrap->getObjectManager()->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface')->compile();
				if ($result === FALSE) {
					self::markTestSkipped('Test skipped because setting up the persistence failed.');
				}
			}
			$this->persistenceManager = $this->objectManager->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		}

			// HTTP must be initialized before security because security relies on an
			// HTTP request being available via the request handler:
		if ($this->testableHttpEnabled === TRUE || $this->testableSecurityEnabled === TRUE) {
			$this->setupHttp();
		}
		if ($this->testableSecurityEnabled === TRUE) {
			$this->setupSecurity();
		}
	}

	/**
	 * Sets up security test requirements
	 *
	 * @return void
	 */
	protected function setupSecurity() {
		$this->accessDecisionManager = $this->objectManager->get('TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$this->accessDecisionManager->setOverrideDecision(NULL);

		$this->authenticationManager = $this->objectManager->get('TYPO3\FLOW3\Security\Authentication\AuthenticationProviderManager');

		$this->testingProvider = $this->objectManager->get('TYPO3\FLOW3\Security\Authentication\Provider\TestingProvider');
		$this->testingProvider->setName('TestingProvider');

		$this->securityContext = $this->objectManager->get('TYPO3\FLOW3\Security\Context');
		$this->securityContext->clearContext();
		$this->securityContext->refreshTokens();

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$actionRequest = $requestHandler->getHttpRequest()->createActionRequest();
		$this->securityContext->setRequest($actionRequest);
	}

	/**
	 * Tears down test requirements depending on the enabled tests
	 *
	 * Note: tearDown() is also called if an exception occurred in one of the tests. If the problem is caused by
	 *       some security or persistence related part of FLOW3, the error might be hard to track because their
	 *       specialized tearDown() methods might cause fatal errors. In those cases just output the original
	 *       exception message by adding an echo($this->statusMessage) as the first line of this method.
	 *
	 * @return void
	 */
	public function tearDown() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->tearDownSecurity();
		}

		$persistenceManager = self::$bootstrap->getObjectManager()->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');

			// Explicitly call persistAll() so that the "allObjectsPersisted" signal is sent even if persistAll()
			// has not been called during a test. This makes sure that for example certain repositories can clear
			// their internal registry in order to avoid side effects in the following test run.
			// Wrap in try/catch to suppress errors after the actual test is run (e.g. validation)
		try {
			$persistenceManager->persistAll();
		} catch (\Exception $exception) {}

		if (is_callable(array($persistenceManager, 'tearDown'))) {
			$persistenceManager->tearDown();
		}

		self::$bootstrap->getObjectManager()->forgetInstance('TYPO3\FLOW3\Http\Client\InternalRequestEngine');
		$this->emitFunctionalTestTearDown();
	}

	/**
	 * Resets security test requirements
	 *
	 * @return void
	 */
	protected function tearDownSecurity() {
		if ($this->accessDecisionManager !== NULL) {
			$this->accessDecisionManager->reset();
		}
		if ($this->testingProvider !== NULL) {
			$this->testingProvider->reset();
		}
		if ($this->securityContext !== NULL) {
			$this->securityContext->clearContext();
		}
	}

	/**
	 * Calls the given action of the given controller
	 *
	 * @param string $controllerName The name of the controller to be called
	 * @param string $controllerPackageKey The package key the controller resides in
	 * @param string $controllerActionName The name of the action to be called, e.g. 'index'
	 * @param array $arguments Optional arguments passed to controller
	 * @param string $format The request format, defaults to 'html'
	 * @return string The result of the controller action
	 * @deprecated since 1.1
	 */
	protected function sendWebRequest($controllerName, $controllerPackageKey, $controllerActionName, array $arguments = array(), $format = 'html') {
		$this->setupHttp();

		$route = new \TYPO3\FLOW3\Mvc\Routing\Route();
		$route->setName('sendWebRequest Route');

		$uriPattern = 'test/' . uniqid();
		$route->setUriPattern($uriPattern);
		$route->setDefaults(array(
			'@package' => $controllerPackageKey,
			'@controller' => $controllerName,
			'@action' => $controllerActionName,
			'@format' => $format
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);

		$uri = new \TYPO3\FLOW3\Http\Uri('http://baseuri/' . $uriPattern);
		$response = $this->browser->request($uri, 'POST', $arguments);

		return $response->getContent();
	}

	/**
	 * Creates a new account, assigns it the given roles and authenticates it.
	 * The created account is returned for further modification, for example for attaching a Party object to it.
	 *
	 * @param array $roleNames A list of roles the new account should have
	 * @return \TYPO3\FLOW3\Security\Account The created account
	 * @api
	 */
	protected function authenticateRoles(array $roleNames) {
		$account = new \TYPO3\FLOW3\Security\Account();
		$roles = array();
		foreach ($roleNames as $roleName) {
			$roles[] = new \TYPO3\FLOW3\Security\Policy\Role($roleName);
		}
		$account->setRoles($roles);
		$this->authenticateAccount($account);

		return $account;
	}

	/**
	 * Prepares the environment for and conducts an account authentication
	 *
	 * @param \TYPO3\FLOW3\Security\Account $account
	 * @return void
	 * @api
	 */
	protected function authenticateAccount(\TYPO3\FLOW3\Security\Account $account) {
		$this->testingProvider->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->testingProvider->setAccount($account);

		$this->securityContext->clearContext();

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$request = $requestHandler->getHttpRequest();

		$actionRequest = $request->createActionRequest();
		$this->securityContext->setRequest($actionRequest);
		$this->authenticationManager->authenticate();
	}

	/**
	 * Disables authorization for the current test
	 *
	 * @return void
	 * @api
	 */
	protected function disableAuthorization() {
		$this->accessDecisionManager->setOverrideDecision(TRUE);
	}

	/**
	 * Adds a route that can be used in the functional tests
	 *
	 * @param string $name Name of the route
	 * @param string $uriPattern The uriPattern property of the route
	 * @param array $defaults An array of defaults declarations
	 * @param boolean $appendExceedingArguments If exceeding arguments may be appended
	 * @return void
	 * @api
	 */
	protected function registerRoute($name, $uriPattern, array $defaults, $appendExceedingArguments = FALSE) {
		$route = new Route();
		$route->setName($name);
		$route->setUriPattern($uriPattern);
		$route->setDefaults($defaults);
		$route->setAppendExceedingArguments($appendExceedingArguments);
		$this->router->addRoute($route);
	}

	/**
	 * Sets up a virtual browser and web environment for seamless HTTP and MVC
	 * related tests.
	 *
	 * @return void
	 */
	protected function setupHttp() {
		$_GET = array();
		$_POST = array();
		$_COOKIE = array();
		$_FILES = array();
		$_SERVER = array (
			'REDIRECT_FLOW3_CONTEXT' => 'Development',
			'REDIRECT_FLOW3_REWRITEURLS' => '1',
			'REDIRECT_STATUS' => '200',
			'FLOW3_CONTEXT' => 'Testing',
			'FLOW3_REWRITEURLS' => '1',
			'HTTP_HOST' => 'localhost',
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
			'HTTP_CONNECTION' => 'keep-alive',
			'PATH' => '/usr/bin:/bin:/usr/sbin:/sbin',
			'SERVER_SIGNATURE' => '',
			'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.3.8',
			'SERVER_NAME' => 'localhost',
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => '80',
			'REMOTE_ADDR' => '127.0.0.1',
			'DOCUMENT_ROOT' => '/opt/local/apache2/htdocs/',
			'SERVER_ADMIN' => 'george@localhost',
			'SCRIPT_FILENAME' => '/opt/local/apache2/htdocs/Web/index.php',
			'REMOTE_PORT' => '51439',
			'REDIRECT_QUERY_STRING' => '',
			'REDIRECT_URL' => '',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
			'REQUEST_TIME' => 1326472534,
		);

		$this->browser = new \TYPO3\FLOW3\Http\Client\Browser();
		$this->browser->setRequestEngine(new \TYPO3\FLOW3\Http\Client\InternalRequestEngine());
		$this->router = $this->browser->getRequestEngine()->getRouter();

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$requestHandler->setHttpRequest(\TYPO3\FLOW3\Http\Request::create(new \TYPO3\FLOW3\Http\Uri('http://localhost')));
	}

	/**
	 * Signals that the functional test case has been executed
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitFunctionalTestTearDown() {
		self::$bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'functionalTestTearDown');
	}
}
?>
