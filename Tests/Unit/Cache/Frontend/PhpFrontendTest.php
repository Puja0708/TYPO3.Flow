<?php
namespace TYPO3\FLOW3\Tests\Unit\Cache\Frontend;

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
 * Testcase for the PHP source code cache frontend
 *
 */
class PhpFrontendTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setChecksIfTheIdentifierIsValid() {
		$cache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array('isValidEntryIdentifier'), array(), '', FALSE);
		$cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(FALSE));
		$cache->set('foo', 'bar');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPassesPhpSourceCodeTagsAndLifetimeToBackend() {
		$originalSourceCode = 'return "hello world!";';
		$modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '#';

		$mockBackend = $this->getMock('TYPO3\FLOW3\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, array('tags'), 1234);

		$cache = $this->getAccessibleMock('TYPO3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);
		$cache->set('Foo-Bar', $originalSourceCode, array('tags'), 1234);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Cache\Exception\InvalidDataException
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$cache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->set('Foo-Bar', array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function requireOnceCallsTheBackendsRequireOnceMethod() {
		$mockBackend = $this->getMock('TYPO3\FLOW3\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

		$cache = $this->getAccessibleMock('TYPO3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);

		$result = $cache->requireOnce('Foo-Bar');
		$this->assertSame('hello world!', $result);
	}
}
?>