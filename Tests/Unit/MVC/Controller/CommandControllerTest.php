<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Controller;

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
 * Testcase for the Command Controller
 */
class CommandControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\CommandController
	 */
	protected $commandController;

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Controller\CommandController', array('dummy'));
	}

	/**
	 * @test
	 */
	public function outputAppendsGivenStringToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\CLI\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', 'some text');
	}

	/**
	 * @test
	 */
	public function outputReplacesArgumentsInGivenString() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\CLI\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
	}

	/**
	 * @test
	 */
	public function outputLineAppendsGivenStringAndNewlineToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\CLI\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text' . PHP_EOL);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('outputLine', 'some text');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\StopActionException
	 */
	public function quitThrowsStopActionException() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\CLI\Response');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\StopActionException
	 */
	public function quitSetsResponseExitCode() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\CLI\Response');
		$mockResponse->expects($this->once())->method('setExitCode')->with(123);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit', 123);
	}
}
?>