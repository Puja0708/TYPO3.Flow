<?php
namespace TYPO3\Flow\Tests\Functional\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Client;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Annotations as Flow;

/**
 * Functional tests for the Router
 *
 * HINT: The routes used in these tests are defined in the Routes.yaml file in the
 *       Testing context of the Flow package configuration.
 */
class RoutingTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * Validate that test routes are loaded
	 */
	public function setUp() {
		parent::setUp();

		$foundRoute = FALSE;
		foreach ($this->router->getRoutes() as $route) {
			if ($route->getName() === 'Flow :: Functional Test: HTTP - FooController') {
				$foundRoute = TRUE;
				break;
			}
		}

		if (!$foundRoute) {
			$this->markTestSkipped('In this distribution the Flow routes are not included into the global configuration.');
			return;
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
	 */
	public function routerThrowsExceptionIfNoRouteCanBeResolved() {
		$this->router = new \TYPO3\Flow\Mvc\Routing\Router();
		$this->router->resolve(array());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameIsEmptyIfNoRouteMatchesCurrentRequest() {
		$this->router = new \TYPO3\Flow\Mvc\Routing\Router();
		$request = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://localhost'));
		$actionRequest = $this->router->route($request);
		$this->assertEquals('', $actionRequest->getControllerObjectName());
	}

	/**
	 * @test
	 */
	public function routerSetsDefaultControllerAndActionIfNotSetByRoute() {
		$this->router = new \TYPO3\Flow\Mvc\Routing\Router();
		$this->registerRoute('Test Route', '', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@format' =>'html'
		));

		$request = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://localhost'));
		$actionRequest = $this->router->route($request);
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\StandardController', $actionRequest->getControllerObjectName());
		$this->assertEquals('index', $actionRequest->getControllerActionName());
	}

	/**
	 *@test
	 */
	public function routeDefaultsOverrideStandardControllerAndAction() {
		$this->router = new \TYPO3\Flow\Mvc\Routing\Router();
		$this->registerRoute('Test Route', '', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@controller' => 'ActionControllerTestA',
			'@action' => 'second',
			'@format' =>'html'
		));

		$request = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://localhost'));
		$actionRequest = $this->router->route($request);
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestAController', $actionRequest->getControllerObjectName());
		$this->assertEquals('second', $actionRequest->getControllerActionName());
	}

	/**
	 * Data provider for routeTests()
	 *
	 * @return array
	 */
	public function routeTestsDataProvider() {
		return array(
				// non existing route is not matched:
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/some/non/existing/route',
				'expectedMatchingRouteName' => NULL
			),

				// static route parts are case sensitive:
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/Upper/Camel/Case',
				'expectedMatchingRouteName' => 'static route parts are case sensitive'
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/upper/camel/case',
				'expectedMatchingRouteName' => NULL
			),

				// dynamic route parts are case insensitive
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/TYPO3.Flow/ActionControllerTestA/index.html',
				'expectedMatchingRouteName' => 'controller route parts are case insensitive',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\ActionControllerTestAController'
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/typo3.flow/actioncontrollertesta/index.HTML',
				'expectedMatchingRouteName' => 'controller route parts are case insensitive',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\ActionControllerTestAController'
			),

				// dynamic route part defaults are overwritten by request path
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/without/default/DynamicOverwritten',
				'expectedMatchingRouteName' => 'dynamic part without default',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('dynamic' => 'DynamicOverwritten')
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/with/default/DynamicOverwritten',
				'expectedMatchingRouteName' => 'dynamic part with default',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('dynamic' => 'DynamicOverwritten')
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default/DynamicOverwritten',
				'expectedMatchingRouteName' => 'optional dynamic part with default',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('optionalDynamic' => 'DynamicOverwritten')
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default',
				'expectedMatchingRouteName' => 'optional dynamic part with default',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('optionalDynamic' => 'OptionalDynamicDefault')
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default',
				'expectedMatchingRouteName' => 'optional dynamic part with default',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('optionalDynamic' => 'OptionalDynamicDefault')
			),

				// toLowerCase has no effect when matching routes
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/case/Dynamic1Overwritten/Dynamic2Overwritten',
				'expectedMatchingRouteName' => 'dynamic part case',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('dynamic1' => 'Dynamic1Overwritten', 'dynamic2' => 'Dynamic2Overwritten')
			),

				// query arguments are ignored when matching routes
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/exceeding/arguments2/FromPath?dynamic=FromQuery',
				'expectedMatchingRouteName' => 'exceeding arguments 02',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('dynamic' => 'FromPath')
			),
			array(
				'requestUri' => 'http://localhost/typo3/flow/test/exceeding/arguments1?dynamic=FromQuery',
				'expectedMatchingRouteName' => 'exceeding arguments 01',
				'expectedControllerObjectName' => 'TYPO3\\Flow\\Tests\\Functional\\Mvc\\Fixtures\\Controller\\RoutingTestAController',
				'expectedArguments' => array('dynamic' => 'DynamicDefault')
			),
		);
	}

	/**
	 * @param string $requestUri request URI
	 * @param string $expectedMatchingRouteName expected route
	 * @param string $expectedControllerObjectName expected controller object name
	 * @param array $expectedArguments expected request arguments after routing or NULL if this should not be checked
	 * @test
	 * @dataProvider routeTestsDataProvider
	 */
	public function routeTests($requestUri, $expectedMatchingRouteName, $expectedControllerObjectName = NULL, array $expectedArguments = NULL) {
		$request = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri($requestUri));
		$actionRequest = $this->router->route($request);
		$matchedRoute = $this->router->getLastMatchedRoute();
		if ($expectedMatchingRouteName === NULL) {
			if ($matchedRoute !== NULL) {
				$this->fail('Expected no route to match URI "' . $requestUri . '" but route "' . $matchedRoute->getName() . '" matched');
			}
		} else {
			if ($matchedRoute === NULL) {
				$this->fail('Expected route "' . $expectedMatchingRouteName . '" to match, but no route matched request URI "' . $requestUri . '"');
			} else {
				$this->assertEquals('Flow :: Functional Test: ' . $expectedMatchingRouteName, $matchedRoute->getName());
			}
		}
		$this->assertEquals($expectedControllerObjectName, $actionRequest->getControllerObjectName());
		if ($expectedArguments !== NULL) {
			$this->assertEquals($expectedArguments, $actionRequest->getArguments());
		}
	}

	/**
	 * Data provider for resolveTests()
	 *
	 * @return array
	 */
	public function resolveTestsDataProvider() {
		$defaults = array('@package' => 'TYPO3.Flow', '@subpackage' => 'Tests\Functional\Mvc\Fixtures', '@controller' => 'RoutingTestA');
		return array(
				// route resolves no matter if defaults are equal to route values
			array(
				'routeValues' => array_merge($defaults, array('dynamic' => 'DynamicDefault')),
				'expectedResolvedRouteName' => 'dynamic part without default',
				'expectedMatchingUri' => 'typo3/flow/test/dynamic/part/without/default/dynamicdefault'
			),
			array(
				'routeValues' => array_merge($defaults, array('dynamic' => 'OverwrittenDynamicValue')),
				'expectedResolvedRouteName' => 'dynamic part without default',
				'expectedMatchingUri' => 'typo3/flow/test/dynamic/part/without/default/overwrittendynamicvalue'
			),

				// if route value is omitted, only routes with a default value resolve
			array(
				'routeValues' => $defaults,
				'expectedResolvedRouteName' => 'dynamic part with default',
				'expectedMatchingUri' => 'typo3/flow/test/dynamic/part/with/default/DynamicDefault'
			),
			array(
				'routeValues' => array_merge($defaults, array('optionalDynamic' => 'OptionalDynamicDefault')),
				'expectedResolvedRouteName' => 'optional dynamic part with default',
				'expectedMatchingUri' => 'typo3/flow/test/optional/dynamic/part/with/default'
			),

				// toLowerCase has an effect on generated URIs
			array(
				'routeValues' => array_merge($defaults, array('dynamic1' => 'DynamicRouteValue1', 'dynamic2' => 'DynamicRouteValue2')),
				'expectedResolvedRouteName' => 'dynamic part case',
				'expectedMatchingUri' => 'typo3/flow/test/dynamic/part/case/DynamicRouteValue1/dynamicroutevalue2'
			),

				// exceeding arguments are appended to resolved URI if appendExceedingArguments is set
			array(
				'routeValues' => array_merge($defaults, array('@action' => 'test1', 'dynamic' => 'DynamicDefault', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar')),
				'expectedResolvedRouteName' => 'exceeding arguments 01',
				'expectedMatchingUri' => 'typo3/flow/test/exceeding/arguments1?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
			),
			array(
				'routeValues' => array_merge($defaults, array('@action' => 'test1', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar', 'dynamic' => 'DynamicOther')),
				'expectedResolvedRouteName' => 'exceeding arguments 02',
				'expectedMatchingUri' => 'typo3/flow/test/exceeding/arguments2/dynamicother?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
			),
		);
	}

	/**
	 * @param array $routeValues route values to resolve
	 * @param string $expectedResolvedRouteName expected route
	 * @param string $expectedMatchingUri expected matching URI
	 * @test
	 * @dataProvider resolveTestsDataProvider
	 */
	public function resolveTests(array $routeValues, $expectedResolvedRouteName, $expectedMatchingUri = NULL) {
		$matchingUri = $this->router->resolve($routeValues);
		$resolvedRoute = $this->router->getLastResolvedRoute();
		if ($expectedResolvedRouteName === NULL) {
			if ($resolvedRoute !== NULL) {
				$this->fail('Expected no route to resolve but route "' . $resolvedRoute->getName() . '" resolved');
			}
		} else {
			if ($resolvedRoute === NULL) {
				$this->fail('Expected route "' . $expectedResolvedRouteName . '" to resolve');
			} else {
				$this->assertEquals('Flow :: Functional Test: ' . $expectedResolvedRouteName, $resolvedRoute->getName());
			}
		}
		$this->assertEquals($expectedMatchingUri, $matchingUri);
	}

}
?>