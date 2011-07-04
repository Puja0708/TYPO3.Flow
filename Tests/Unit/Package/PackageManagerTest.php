<?php
namespace TYPO3\FLOW3\Tests\Unit\Package;

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

use \TYPO3\FLOW3\Package\PackageInterface;

/**
 * Testcase for the default package manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PackageManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Test'));
		$mockBootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getSignalSlotDispatcher')->will($this->returnValue($this->getMock('TYPO3\FLOW3\SignalSlot\Dispatcher')));
		$this->packageManager = new \TYPO3\FLOW3\Package\PackageManager();

		mkdir('vfs://Test/Resources');
		$packageClassTemplateUri = 'vfs://Test/Resources/Package.php.tmpl';
		file_put_contents($packageClassTemplateUri, '<?php namespace {packageNamespace}; # The {packageKey} package');
		$this->packageManager->setPackageClassTemplateUri($packageClassTemplateUri);

		mkdir('vfs://Test/Packages/Application', 0700, TRUE);
		mkdir('vfs://Test/Configuration');

		$mockClassLoader = $this->getMock('TYPO3\FLOW3\Core\ClassLoader', array(), array(), '', FALSE);

		$this->packageManager->injectClassLoader($mockClassLoader);
		$this->packageManager->initialize($mockBootstrap, 'vfs://Test/Packages/', 'vfs://Test/Configuration/PackageStates.php');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeUsesPackageStatesConfigurationForActivePackages() {
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageReturnsTheSpecifiedPackage() {
		$this->packageManager->createPackage('TYPO3.FLOW3');

		$package = $this->packageManager->getPackage('TYPO3.FLOW3');
		$this->assertInstanceOf('TYPO3\FLOW3\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \TYPO3\FLOW3\Package\Exception\UnknownPackageException
	 */
	public function getPackageThrowsExcpetionOnUnknownPackage() {
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'), array(), '', FALSE);
		$packageManager->_set('packageKeys', array('acme.testpackage' => 'Acme.TestPackage'));
		$this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
	}

	/**
	 * Data Provider returning valid package keys and the corresponding path
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function packageKeysAndPaths() {
		return array(
			array('TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3/YetAnotherTestPackage/'),
			array('RobertLemke.FLOW3.NothingElse', 'vfs://Test/Packages/Application/RobertLemke/FLOW3/NothingElse/')
		);
	}

	/**
	 * @test
	 * @dataProvider packageKeysAndPaths
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds() {
		$packageKeys = array(
			'TYPO3.FLOW3' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.FLOW3.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($packageKeys as $packageKey) {
			$packageNamespace = str_replace('.', '\\', $packageKey);
			$packagePath = 'vfs://Test/Packages/Application/' . str_replace('.', '/', $packageNamespace) . '/';
			$packageClassCode = '<?php
					namespace ' . $packageNamespace . ';
					class Package extends \TYPO3\FLOW3\Package\Package {}
			?>';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			mkdir($packagePath . 'Meta');
			file_put_contents($packagePath . 'Classes/Package.php', $packageClassCode);
			file_put_contents($packagePath . 'Meta/Package.xml', '<xml>...</xml>');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'), array(), '', FALSE);
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Package\Exception\CorruptPackageException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function scanAvailablePackagesThrowsAnExceptionWhenItFindsACorruptPackage() {
		mkdir('vfs://Test/Packages/Application/TYPO3/YetAnotherTestPackage/Meta', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/TYPO3/YetAnotherTestPackage/Meta/Package.xml', '<xml>...</xml>');

		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'), array(), '', FALSE);
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageManager->_call('scanAvailablePackages');
	}

	/**
	 * @test
	 * @dataProvider packageKeysAndPaths
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath) {
		$actualPackage = $this->packageManager->createPackage($packageKey);
		$actualPackagePath = $actualPackage->getPackagePath();

		$this->assertEquals($expectedPackagePath, $actualPackagePath);
		$this->assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
		$this->assertEquals($packageKey, $actualPackage->getPackageKey());
		$this->assertTrue($this->packageManager->isPackageAvailable($packageKey));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageWritesAPackageMetaFileUsingTheGivenMetaObject() {
		$metaData = new \TYPO3\FLOW3\Package\MetaData('Acme.YetAnotherTestPackage');
		$metaData->setTitle('Yet Another Test Package');

		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', $metaData);

		$actualPackageXml = simplexml_load_file($package->getMetaPath() . 'Package.xml');
		$this->assertEquals('Acme.YetAnotherTestPackage', (string)$actualPackageXml->key);
		$this->assertEquals('Yet Another Test Package', (string)$actualPackageXml->title);
	}

	/**
	 * Checks if createPackage() creates the folders for classes, configuration, documentation, resources and tests and
	 * the mandatory Package class.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageCreatesCommonFoldersAndThePackageClass() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$packagePath = $package->getPackagePath();

		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CLASSES), "Classes directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), "Configuration directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_DOCUMENTATION), "Documentation directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_RESOURCES), "Resources directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_UNIT), "Tests/Unit directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_FUNCTIONAL), "Tests/Functional directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA), "Metadata directory was not created");

		$actualPackageClassCode = file_get_contents($packagePath . PackageInterface::DIRECTORY_CLASSES . 'Package.php');
		$expectedPackageClassCode = '<?php namespace Acme\YetAnotherTestPackage; # The Acme.YetAnotherTestPackage package';
		$this->assertEquals($expectedPackageClassCode, $actualPackageClassCode);
	}

	/**
	 * Makes sure that an exception is thrown and no directory is created on passing invalid package keys.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageThrowsExceptionOnInvalidPackageKey() {
		try {
			$this->packageManager->createPackage('Invalid_PackageKey');
		} catch(\TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException $exception) {
		}
		$this->assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
	}

	/**
	 * Makes sure that duplicate package keys are detected.
	 *
	 * @test
	 * @expectedException TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageThrowsExceptionForExistingPackageKey() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createPackageActivatesTheNewlyCreatedPackage() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage() {
		$packageKey = 'Acme.YetAnotherTestPackage';

		$this->packageManager->createPackage($packageKey);

		$this->packageManager->deactivatePackage($packageKey);
		$this->assertFalse($this->packageManager->isPackageActive($packageKey));

		$this->packageManager->activatePackage($packageKey);
		$this->assertTrue($this->packageManager->isPackageActive($packageKey));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deactivatePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\UnknownPackageException
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function deletePackageThrowsErrorIfPackageIsNotAvailable() {
		$this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deletePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$packagePath = $package->getPackagePath();

		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
		$this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
		$this->assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

		$this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

		$this->assertFalse(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
		$this->assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
		$this->assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
	}
}
?>