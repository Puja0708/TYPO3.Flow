<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web\Routing;

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
 * Testcase for the MVC Web Router
 *
 */
class RouterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resolveCallsCreateRoutesFromConfiguration() {
		$mockLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');
		$router = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\Router', array('createRoutesFromConfiguration'));
		$router->injectSystemLogger($mockLogger);

			// not saying anything, but seems better than to expect the exception we'd get otherwise
		$mockRoute = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route');
		$mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(TRUE));
		$mockRoute->expects($this->once())->method('getMatchingUri')->will($this->returnValue('foobar'));
		$router->_set('routes', array($mockRoute));

			// this we actually want to know
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->resolve(array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createRoutesFromConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt() {
		$mockLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');

		$routesConfiguration = array();
		$routesConfiguration['route1']['uriPattern'] = 'number1';
		$routesConfiguration['route2']['uriPattern'] = 'number2';
		$routesConfiguration['route3'] = array(
			'name' => 'route3',
			'defaults' => array('foodefault'),
			'routeParts' => array('fooroutepart'),
			'uriPattern' => 'number3',
			'toLowerCase' => TRUE
		);

		$router = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\Router', array('dummy'));
		$router->injectSystemLogger($mockLogger);
		$router->setRoutesConfiguration($routesConfiguration);
		$router->_call('createRoutesFromConfiguration');
		$createdRoutes = $router->_get('routes');

		$this->assertEquals('number1', $createdRoutes[0]->getUriPattern());
		$this->assertEquals('number2', $createdRoutes[1]->getUriPattern());
		$this->assertEquals('route3', $createdRoutes[2]->getName());
		$this->assertEquals(array('foodefault'), $createdRoutes[2]->getDefaults());
		$this->assertEquals(array('fooroutepart'), $createdRoutes[2]->getRoutePartsConfiguration());
		$this->assertEquals('number3', $createdRoutes[2]->getUriPattern());
		$this->assertEquals(TRUE, $createdRoutes[2]->isLowerCase());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheMatchingUriIfAny() {
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route', array('resolves', 'getMatchingUri'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->once())->method('getMatchingUri')->will($this->returnValue('route2'));

		$route3 = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);

		$mockRoutes = array($route1, $route2, $route3);
		$mockLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');

		$router = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\Router', array('createRoutesFromConfiguration'), array(), '', FALSE);
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->_set('routes', $mockRoutes);
		$router->injectSystemLogger($mockLogger);

		$matchingUri = $router->resolve($routeValues);
		$this->assertSame('route2', $matchingUri);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\NoMatchingRouteException
	 */
	public function resolveThrowsExceptionIfNoMatchingRouteWasFound() {
		$route1 = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route');
		$route1->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\Route');
		$route2->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$mockRoutes = array($route1, $route2);
		$mockLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');

		$router = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\Router', array('createRoutesFromConfiguration'));
		$router->_set('routes', $mockRoutes);
		$router->injectSystemLogger($mockLogger);

		$router->resolve(array());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function packageKeyCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@package' => 'MyCompany.MyPackage')));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with($this->equalTo('MyCompany.MyPackage'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subpackageKeyCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@subpackage' => 'MySubpackage')));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerSubpackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with($this->equalTo('MySubpackage'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function controllerNameCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@controller' => 'MyController')));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerName')->with($this->equalTo('MyController'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 */
	public function controllerNameAndActionAreSetToDefaultIfNotSpecifiedInArguments() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->will($this->returnValue(array()));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'getControllerName', 'setControllerName', 'getControllerActionName', 'setControllerActionName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$mockRequest->expects($this->once())->method('setControllerName')->with($this->equalTo('Standard'));

		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$mockRequest->expects($this->once())->method('setControllerActionName')->with($this->equalTo('index'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function actionNameCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@action' => 'myAction')));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getControllerActionName', 'setControllerActionName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('myAction');
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('myAction'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function formatCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@format' => 'myFormat')));

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array('getRoutePath', 'setFormat', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setFormat')->with($this->equalTo('myFormat'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue('TestPackage\Controller\FooController'));

		$router = new \TYPO3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);
		$this->assertEquals('TestPackage\Controller\FooController', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\bar\baz\Controller\fooController'))
			->will($this->returnValue('TestPackage\Bar\Baz\Controller\FooController'));

		$router = new \TYPO3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals('TestPackage\Bar\Baz\Controller\FooController', $router->getControllerObjectName('testpackage', 'bar\baz', 'foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getControllerObjectNameReturnsNullIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue(FALSE));

		$router = new \TYPO3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals('', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * Data Provider
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectNameArguments() {
		return array(
			array('MyPackage', NULL, 'MyController', 'MyPackage\Controller\MyControllerController'),
			array('MyCompany.MyPackage', NULL, 'MyController', 'MyCompany\MyPackage\Controller\MyControllerController'),
			array('Com.FineDudeArt.Gallery', 'Media', 'Image', 'Com\FineDudeArt\Gallery\Media\Controller\ImageController')
		);
	}

	/**
	 * @test
	 * @dataProvider getControllerObjectNameArguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectNameReturnsCorrectObjectNamesBasedOnTheGivenArguments($packageKey, $subpackageKey, $controllerName, $expectedObjectName) {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->will($this->returnArgument(0));

		$router = new \TYPO3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals($expectedObjectName, $router->getControllerObjectName($packageKey, $subpackageKey, $controllerName));
	}

	protected function getRouter() {
		return $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\Router', array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
	}

}
?>