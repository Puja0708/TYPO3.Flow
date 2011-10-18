<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\EntryPoint;

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
 * Testcase for HTTP Basic Auth authentication entry point
 *
 */
class HttpBasicTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 */
	public function canForwardReturnsTrueForWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$this->assertTrue($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\Web\Request')));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function canForwardReturnsFalseForNonWebRequests() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\CLI\Request')));
		$this->assertFalse($entryPoint->canForward($this->getMock('TYPO3\FLOW3\MVC\RequestInterface')));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function startAuthenticationThrowsAnExceptionIfItsCalledWithAnUnsupportedRequestType() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();

		$entryPoint->startAuthentication($this->getMock('TYPO3\FLOW3\MVC\CLI\Request'), $this->getMock('TYPO3\FLOW3\MVC\CLI\Response'));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$response = $this->getMock('TYPO3\FLOW3\MVC\Web\Response', array('setStatus', 'setContent', 'setHeader'));

		$response->expects($this->once())->method('setStatus')->with(401);
		$response->expects($this->once())->method('setHeader')->with('WWW-Authenticate', 'Basic realm="realm string"');
		$response->expects($this->once())->method('setContent')->with('Authorization required!');

		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\HttpBasic();
		$entryPoint->setOptions(array('realm' => 'realm string'));

		$entryPoint->startAuthentication($request, $response);
	}
}
?>