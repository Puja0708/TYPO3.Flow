<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\View;

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
 * Testcase for the JSON view
 *
 */
class JsonViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\View\JsonView
	 */
	protected $view;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \TYPO3\FLOW3\Http\Response
	 */
	protected $response;

	/**
	 * Sets up this test case
	 * @return void
	 */
	public function setUp() {
		$this->view = $this->getMock('TYPO3\FLOW3\Mvc\View\JsonView', array('loadConfigurationFromYamlFile'));
		$this->controllerContext = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->response = $this->getMock('TYPO3\FLOW3\Http\Response', array());
		$this->controllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
		$this->view->setControllerContext($this->controllerContext);
	}

	/**
	 * data provider for testTransformValue()
	 * @return array
	 */
	public function jsonViewTestData() {
		$output = array();

		$object = new \stdClass();
		$object->value1 = 'foo';
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value1' => 'foo', 'value2' => 1);
		$output[] = array($object, $configuration, $expected, 'all direct child properties should be serialized');

		$configuration = array('_only' => array('value1'));
		$expected = array('value1' => 'foo');
		$output[] = array($object, $configuration, $expected, 'if "only" properties are specified, only these should be serialized');

		$configuration = array('_exclude' => array('value1'));
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'if "exclude" properties are specified, they should not be serialized');

		$object = new \stdClass();
		$object->value1 = new \stdClass();
		$object->value1->subvalue1 = 'Foo';
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'by default, sub objects of objects should not be serialized.');

		$object = new \stdClass();
		$object->value1 = array('subarray' => 'value');
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'by default, sub arrays of objects should not be serialized.');

		$object = array('foo' => 'bar', 1 => 'baz', 'deep' => array('test' => 'value'));
		$configuration = array();
		$expected = array('foo' => 'bar', 1 => 'baz', 'deep' => array('test' => 'value'));
		$output[] = array($object, $configuration, $expected, 'associative arrays should be serialized deeply');

		$object = array('foo', 'bar');
		$configuration = array();
		$expected = array('foo', 'bar');
		$output[] = array($object, $configuration, $expected, 'numeric arrays should be serialized');

		$nestedObject = new \stdClass();
		$nestedObject->value1 = 'foo';
		$object = array($nestedObject);
		$configuration = array();
		$expected = array(array('value1' => 'foo'));
		$output[] = array($object, $configuration, $expected, 'array of objects should be serialized');

		$properties = array('foo' => 'bar', 'prohibited' => 'xxx');
		$nestedObject = $this->getMock('Test' . md5(uniqid(mt_rand(), TRUE)), array('getName', 'getPath', 'getProperties', 'getOther'));
		$nestedObject->expects($this->any())->method('getName')->will($this->returnValue('name'));
		$nestedObject->expects($this->any())->method('getPath')->will($this->returnValue('path'));
		$nestedObject->expects($this->any())->method('getProperties')->will($this->returnValue($properties));
		$nestedObject->expects($this->never())->method('getOther');
		$object = $nestedObject;
		$configuration = array(
			'_only' => array('name', 'path', 'properties'),
			'_descend' => array(
				 'properties' => array(
					  '_exclude' => array('prohibited')
				 )
			)
		);
		$expected = array(
			'name' => 'name',
			'path' => 'path',
			'properties' => array('foo' => 'bar')
		);
		$output[] = array($object, $configuration, $expected, 'descending into arrays should be possible');

		$nestedObject = new \stdClass();
		$nestedObject->value1 = 'foo';
		$value = new \SplObjectStorage();
		$value->attach($nestedObject);
		$configuration = array();
		$expected = array(array('value1' => 'foo'));
		$output[] = array($value, $configuration, $expected, 'SplObjectStorage with objects should be serialized');

		$dateTimeObject = new \DateTime('2011-02-03T03:15:23');
		$configuration = array();
		$expected = '2011-02-03T03:15:23';
		$output[] = array($dateTimeObject, $configuration, $expected, 'DateTime object could not be serialized.');
		return $output;
	}

	/**
	 * @test
	 * @dataProvider jsonViewTestData
	 */
	public function testTransformValue($object, $configuration, $expected, $description) {
		$jsonView = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\View\JsonView', array('dummy'), array(), '', FALSE);

		$actual = $jsonView->_call('transformValue', $object, $configuration);

		$this->assertEquals($expected, $actual, $description);
	}

	/**
	 * @test
	 */
	public function renderSetsContentTypeHeader() {
		$this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'application/json');

		$this->view->render();
	}

	/**
	 * @test
	 */
	public function renderReturnsJsonRepresentationOfAssignedObject() {
		$object = new \stdClass();
		$object->foo = 'Foo';
		$this->view->assign('value', $object);

		$expectedResult = '{"foo":"Foo"}';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsJsonRepresentationOfAssignedArray() {
		$array = array('foo' => 'Foo', 'bar' => 'Bar');
		$this->view->assign('value', $array);

		$expectedResult = '{"foo":"Foo","bar":"Bar"}';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsJsonRepresentationOfAssignedSimpleValue() {
		$value = 'Foo';
		$this->view->assign('value', $value);

		$expectedResult = '"Foo"';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsNullIfNameOfAssignedVariableIsNotEqualToValue() {
		$value = 'Foo';
		$this->view->assign('foo', $value);

		$expectedResult = 'null';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderOnlyRendersVariableWithTheNameValue() {
		$this->view
			->assign('value', 'Value')
			->assign('someOtherVariable', 'Foo');

		$expectedResult = '"Value"';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function setVariablesToRenderOverridesValueToRender() {
		$value = 'Foo';
		$this->view->assign('foo', $value);
		$this->view->setVariablesToRender(array('foo'));

		$expectedResult = '"Foo"';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderRendersMultipleValuesIfTheyAreSpecifiedAsVariablesToRender() {
		$this->view
			->assign('value', 'Value1')
			->assign('secondValue', 'Value2')
			->assign('someOtherVariable', 'Value3');
		$this->view->setVariablesToRender(array('value', 'secondValue'));

		$expectedResult = '{"value":"Value1","secondValue":"Value2"}';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderCanRenderMultipleComplexObjects() {
		$array = array('foo' => array('bar' => 'Baz'));
		$object = new \stdClass();
		$object->foo = 'Foo';

		$this->view
			->assign('array', $array)
			->assign('object', $object)
			->assign('someOtherVariable', 'Value3');
		$this->view->setVariablesToRender(array('array', 'object'));

		$expectedResult = '{"array":{"foo":{"bar":"Baz"}},"object":{"foo":"Foo"}}';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderCanRenderPlainArray() {
		$array = array(array('name' => 'Foo', 'secret' => TRUE), array('name' => 'Bar', 'secret' => TRUE));

		$this->view->assign('value', $array);
		$this->view->setConfiguration(array(
			'value' => array(
				'_descendAll' => array(
					'_only' => array('name')
				)
			)
		));

		$expectedResult = '[{"name":"Foo"},{"name":"Bar"}]';
		$actualResult = $this->view->render();
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>