<?php
namespace TYPO3\FLOW3\Tests\Unit\Cache;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once ('Backend/MockBackend.php');

/**
 * Testcase for the Cache Factory
 *
 */
class CacheFactoryTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * Creates the mocked filesystem used in the tests
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		$this->mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \TYPO3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $this->mockEnvironment);

		$cache = $factory->create('TYPO3_FLOW3_Cache_FactoryTest_Cache', 'TYPO3\FLOW3\Cache\Frontend\VariableFrontend', 'TYPO3\FLOW3\Cache\Backend\NullBackend');
		$this->assertInstanceOf('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', $cache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \TYPO3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $this->mockEnvironment);

		$cache = $factory->create('TYPO3_FLOW3_Cache_FactoryTest_Cache', 'TYPO3\FLOW3\Cache\Frontend\VariableFrontend', 'TYPO3\FLOW3\Cache\Backend\FileBackend');
		$this->assertInstanceOf('TYPO3\FLOW3\Cache\Backend\FileBackend', $cache->getBackend());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createRegistersTheCacheAtTheCacheManager() {
		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache');

		$factory = new \TYPO3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $this->mockEnvironment);

		$factory->create('TYPO3_FLOW3_Cache_FactoryTest_Cache', 'TYPO3\FLOW3\Cache\Frontend\VariableFrontend', 'TYPO3\FLOW3\Cache\Backend\FileBackend');
	}
}
?>