<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\CLI;

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

use \TYPO3\FLOW3\MVC\CLI\Command;

/**
 * Testcase for the CLI Command class
 */
class CommandTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\CLI\Command
	 */
	protected $command;

	/**
	 * @var \TYPO3\FLOW3\Reflection\MethodReflection
	 */
	protected $mockMethodReflection;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->command = $this->getAccessibleMock('TYPO3\FLOW3\MVC\CLI\Command', array('getCommandMethodReflection'), array(), '', FALSE);
		$this->mockMethodReflection = $this->getMock('TYPO3\FLOW3\Reflection\MethodReflection', array(), array(), '', FALSE);
		$this->command->expects($this->any())->method('getCommandMethodReflection')->will($this->returnValue($this->mockMethodReflection));
	}

	/**
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function commandIdentifiers() {
		return array(
			array('TYPO3\FLOW3\Command\CacheCommandController', 'flush', 'typo3.flow3:cache:flush'),
			array('RobertLemke\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'robertlemke.foo.faa.fuuum:coffee:brew'),
			array('SomePackage\Command\CookieCommandController', 'bake', 'somepackage:cookie:bake')
		);
	}

	/**
	 * @test
	 * @dataProvider commandIdentifiers
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier) {
		$command = new Command($controllerClassName, $commandName);
		$this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertFalse($this->command->hasArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasArgumentsReturnsTrueIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('TYPO3\FLOW3\Reflection\ParameterReflection', array(), array(), '', FALSE);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->assertTrue($this->command->hasArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertSame(array(), $this->command->getArgumentDefinitions());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('TYPO3\FLOW3\Reflection\ParameterReflection', array(), array(), '', FALSE);
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockMethodParameters = array('argument1' => array('optional' => FALSE), 'argument2' => array('optional' => TRUE));
		$mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue($mockMethodParameters));
		$this->command->injectReflectionService($mockReflectionService);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getTagsValues')->will($this->returnValue(array('param' => array('@param $argument1 argument1 description', '@param $argument2 argument2 description'))));

		$expectedResult = array(
			new \TYPO3\FLOW3\MVC\CLI\CommandArgumentDefinition('argument1', TRUE, 'argument1 description'),
			new \TYPO3\FLOW3\MVC\CLI\CommandArgumentDefinition('argument2', FALSE, 'argument2 description')
		);
		$actualResult = $this->command->getArgumentDefinitions();
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>