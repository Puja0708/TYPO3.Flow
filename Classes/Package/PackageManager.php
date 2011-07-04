<?php
namespace TYPO3\FLOW3\Package;

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

use \TYPO3\FLOW3\Package\MetaData\XmlWriter as PackageMetaDataWriter;
use \TYPO3\FLOW3\Package\Package;
use \TYPO3\FLOW3\Package\PackageInterface;
use \TYPO3\FLOW3\Utility\Files;

/**
 * The default TYPO3 Package Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class PackageManager implements \TYPO3\FLOW3\Package\PackageManagerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * Array of available packages, indexed by package key
	 * @var array
	 */
	protected $packages = array();

	/**
	 * A translation table between lower cased and upper camel cased package keys
	 * @var array
	 */
	protected $packageKeys = array();

	/**
	 * List of active packages as package key => package object
	 * @var array
	 */
	protected $activePackages = array();

	/**
	 * Absolute path leading to the various package directories
	 * @var string
	 */
	protected $packagesBasePath;

	/**
	 * @var string
	 */
	protected $packageStatesPathAndFilename;

	/**
	 * Package states configuration as stored in the PackageStates.php file
	 * @var array
	 */
	protected $packageStatesConfiguration = array();

	/**
	 * @var string
	 */
	protected $packageClassTemplateUri = 'resource://TYPO3.FLOW3/Private/Package/Package.php.tmpl';

	/**
	 * @param \TYPO3\FLOW3\Core\ClassLoader $classLoader
	 * @return void
	 */
	public function injectClassLoader(\TYPO3\FLOW3\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * Sets the URI specifying the file acting as a template for the Package class files of newly created packages.
	 *
	 * @param $packageClassTemplateUri Full path and filename or other valid URI pointing to the template file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPackageClassTemplateUri($packageClassTemplateUri) {
		$this->packageClassTemplateUri = $packageClassTemplateUri;
	}

	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @param $packagesBasePath Absolute path of the Packages directory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize(\TYPO3\FLOW3\Core\Bootstrap $bootstrap, $packagesBasePath = FLOW3_PATH_PACKAGES, $packageStatesPathAndFilename = '') {
		$this->packagesBasePath = $packagesBasePath;
		$this->scanAvailablePackages();
		$this->packageStatesPathAndFilename = ($packageStatesPathAndFilename === '') ? FLOW3_PATH_CONFIGURATION . 'PackageStates.php' : $packageStatesPathAndFilename;

		$this->loadPackageStates();

		foreach ($this->packages as $packageKey => $package) {
			if ($package->isProtected() || (isset($this->packageStatesConfiguration[$packageKey]['state']) && $this->packageStatesConfiguration[$packageKey]['state'] === 'active')) {
				$this->activePackages[$packageKey] = $package;
			}
		}

		$this->classLoader->setPackages($this->activePackages);

		foreach ($this->activePackages as $package) {
			$package->boot($bootstrap);
		}

	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isPackageAvailable($packageKey) {
		return (isset($this->packages[$packageKey]));
	}

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @api
	 */
	public function isPackageActive($packageKey) {
		return (isset($this->activePackages[$packageKey]));
	}

	/**
	 * Returns a PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid MetaData information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\FLOW3\Package The requested package object
	 * @throws \TYPO3\FLOW3\Package\Exception\UnknownPackageException if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
		}
		return $this->packages[$packageKey];
	}

	/**
	 * Returns an array of \TYPO3\FLOW3\Package objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getAvailablePackages() {
		return $this->packages;
	}

	/**
	 * Returns an array of \TYPO3\FLOW3\Package objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getActivePackages() {
		return $this->activePackages;
	}

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $unknownCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey) {
		$lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
		return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : FALSE;
	}

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @return boolean If the package key is valid, returns TRUE otherwise FALSE
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function isPackageKeyValid($packageKey) {
		return preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
	}

	/**
	 * Create a package, given the package key
	 *
	 * @param string $packageKey The package key of the new package
	 * @param \TYPO3\FLOW3\Package\MetaData $packageMetaData If specified, this package meta object is used for writing the Package.xml file, otherwise a rudimentary Package.xml file is created
	 * @param string $packagesPath If specified, the package will be created in this path, otherwise the default "Application" directory is used
	 * @return \TYPO3\FLOW3\Package\Package The newly created package
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function createPackage($packageKey, \TYPO3\FLOW3\Package\MetaData $packageMetaData = NULL, $packagesPath = '') {
		if (!$this->isPackageKeyValid($packageKey)) throw new \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException('The package key "' . $packageKey . '" is invalid', 1220722210);
		if ($this->isPackageAvailable($packageKey)) throw new \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException('The package key "' . $packageKey . '" already exists', 1220722873);

		if ($packageMetaData === NULL) {
			$packageMetaData = new \TYPO3\FLOW3\Package\MetaData($packageKey);
		}

		if ($packagesPath === '') {
			$packagesPath = Files::getUnixStylePath(Files::concatenatePaths(array($this->packagesBasePath, 'Application')));
		}

		$packagePath = Files::concatenatePaths(array($packagesPath, str_replace('.', '/', $packageKey))) . '/';
		Files::createDirectoryRecursively($packagePath);

		foreach (
			array(
				PackageInterface::DIRECTORY_METADATA,
				PackageInterface::DIRECTORY_CLASSES,
				PackageInterface::DIRECTORY_CONFIGURATION,
				PackageInterface::DIRECTORY_DOCUMENTATION,
				PackageInterface::DIRECTORY_RESOURCES,
				PackageInterface::DIRECTORY_TESTS_UNIT,
				PackageInterface::DIRECTORY_TESTS_FUNCTIONAL,
			) as $path) {
			Files::createDirectoryRecursively(Files::concatenatePaths(array($packagePath, $path)));
		}

		$package = new Package($packageKey, $packagePath);
		$result = PackageMetaDataWriter::writePackageMetaData($package, $packageMetaData);
		if ($result === FALSE) throw new \TYPO3\FLOW3\Package\Exception('Error while writing the package meta data information at "' . $packagePath . '"', 1232625240);

		$packageNamespace = str_replace('.', '\\', $packageKey);
		$packagePhpSource = str_replace('{packageKey}', $packageKey, Files::getFileContents($this->packageClassTemplateUri));
		$packagePhpSource = str_replace('{packageNamespace}', $packageNamespace, $packagePhpSource);
		file_put_contents($package->getClassesPath() . 'Package.php', $packagePhpSource);

		$this->packages[$packageKey] = $package;
		foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
			$this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
		}

		$this->activatePackage($packageKey);

		return $package;
	}

	/**
	 * Deactivates a package
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function deactivatePackage($packageKey) {
		if (!$this->isPackageActive($packageKey)) {
			return FALSE;
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1308662891);
		}

		unset($this->activePackages[$packageKey]);
		$this->packageStatesConfiguration[$packageKey]['state'] = 'inactive';
		$this->savePackageStates();
	}

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function activatePackage($packageKey) {
		if ($this->isPackageActive($packageKey)) {
			return FALSE;
		}

		$package = $this->getPackage($packageKey);
		$this->activePackages[$packageKey] = $package;
		$this->packageStatesConfiguration[$packageKey]['state'] = 'active';
		$this->savePackageStates();
	}

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to remove
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\UnknownPackageException if the specified package is not known
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function deletePackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
		}

		if ($this->isPackageActive($packageKey)) {
			$this->deactivatePackage($packageKey);
		}

		$packagePath = $package->getPackagePath();
		try {
			Files::removeDirectoryRecursively($packagePath);
		} catch (\TYPO3\FLOW3\Utility\Exception $exception) {
			throw new \TYPO3\FLOW3\Package\Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089, $exception);
		}

		unset($this->packages[$packageKey]);
		unset($this->packageKeys[strtolower($packageKey)]);
	}

	/**
	 * Scans all directories in the packages directories for available packages.
	 * For each package a Package object is created and stored in $this->packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function scanAvailablePackages() {
		$packagePaths = array();
		foreach (new \DirectoryIterator($this->packagesBasePath) as $parentFileInfo) {
			$parentFilename = $parentFileInfo->getFilename();
			if ($parentFilename[0] !== '.' && $parentFileInfo->isDir()) {
				$packagePaths = array_merge($packagePaths, $this->scanPackagesInPath($parentFileInfo->getPathName()));
			}
		}

		foreach ($packagePaths as $packagePath) {
			$relativePackagePath = substr($packagePath, strlen($this->packagesBasePath));
			$packageKey = str_replace('/', '.', substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1));

			if (isset($this->packages[$packageKey])) {
				throw new \TYPO3\FLOW3\Package\Exception\DuplicatePackageException('Detected a duplicate package, remove either "' . $this->packages[$packageKey]->getPackagePath() . '" or "' . $packagePath . '".', 1253716811);
			}

			$packageClassPathAndFilename = $packagePath . 'Classes/Package.php';
			if (!file_exists($packageClassPathAndFilename)) {
				$shortFilename = substr($packagePath, strlen($this->packagesBasePath)) . 'Classes/Package.php';
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('Missing package class in package "%s". Please create a file "%s" and extend Package.', $packageKey, $shortFilename), 1300782486);
			}

			require_once($packageClassPathAndFilename);
			$packageClassName = str_replace('.', '\\', $packageKey) . '\Package';
			$this->packages[$packageKey] = new $packageClassName($packageKey, $packagePath);
			if (!$this->packages[$packageKey] instanceof PackageInterface) {
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('The package class %s in package "%s" does not implement PackageInterface.', $packageClassName, $packageKey), 1300782487);
			}

			$this->packageKeys[strtolower($packageKey)] = $packageKey;
		}
	}

	/**
	 * Scans the all sub directories of the specified directory and collects the package keys of packages it finds.
	 * If this method finds a corrupt package, an exception is thrown.
	 *
	 * @param string $startPath
	 * @return void
	 */
	protected function scanPackagesInPath($startPath, &$collectedPackagePaths = array()) {
		foreach (new \DirectoryIterator($startPath) as $fileInfo) {
			$filename = $fileInfo->getFilename();
			if ($filename[0] !== '.') {
				$packagePath = Files::getUnixStylePath($fileInfo->getPathName()) . '/';
				$packageMetaPathAndFilename = $packagePath . 'Meta/Package.xml';
				if (file_exists($packageMetaPathAndFilename)) {
					$collectedPackagePaths[] = $packagePath;
				} elseif ($fileInfo->isDir() && $filename[0] !== '.') {
					$this->scanPackagesInPath($packagePath, $collectedPackagePaths);
				}
			}
		}
		return $collectedPackagePaths;
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function loadPackageStates() {
		$this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
		if ($this->packageStatesConfiguration === array()) {
			foreach ($this->packageKeys as $packageKey) {
				$this->activePackages[$packageKey] = $this->packages[$packageKey];
				$this->packageStatesConfiguration[$packageKey]['state'] = 'active';
			}
			$this->savePackageStates();
		}
	}

	/**
	 * Saves the current content of $this->packageStatesConfiguration to the PackageStates.php file.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function savePackageStates() {
		$packageStatesCode = "<?php\nreturn " . var_export($this->packageStatesConfiguration, TRUE) . "\n ?>";
		file_put_contents($this->packageStatesPathAndFilename, $packageStatesCode);
		if (strpos($this->packageStatesPathAndFilename, '://') === FALSE) {
			chmod($this->packageStatesPathAndFilename, 0660);
		}
	}
}

?>