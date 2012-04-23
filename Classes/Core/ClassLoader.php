<?php
namespace TYPO3\FLOW3\Core;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @FLOW3\Proxy(false)
 * @FLOW3\Scope("singleton")
 */
class ClassLoader {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * An array of \TYPO3\FLOW3\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * @var string
	 */
	protected $packagesPath = FLOW3_PATH_PACKAGES;

	/**
	 * A list of namespaces this class loader is definitely responsible for
	 * @var array
	 */
	protected $packageNamespaces = array(
		'TYPO3\FLOW3' => 11
	);

	/**
	 * @var boolean
	 */
	protected $considerTestsNamespace = FALSE;

	/**
	 * @var array
	 */
	protected $ignoredClassNames = array(
		'integer' => TRUE,
		'string' => TRUE,
		'param' => TRUE,
		'return' => TRUE,
		'var' => TRUE,
		'throws' => TRUE,
		'api' => TRUE,
		'todo' => TRUE,
		'fixme' => TRUE,
		'see' => TRUE,
		'license' => TRUE,
		'author' => TRUE,
		'test' => TRUE,
	);

	/**
	 * Injects the cache for storing the renamed original classes
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 */
	public function injectClassesCache(\TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return boolean
	 */
	public function loadClass($className) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}

			// Loads any known proxied class:
		if ($this->classesCache !== NULL && $this->classesCache->requireOnce(str_replace('\\', '_', $className)) !== FALSE) {
			return TRUE;
		}

			// Workaround for Doctrine's annotation parser which does a class_exists() for annotations like "@param" and so on:
		if (isset($this->ignoredClassNames[$className]) || isset($this->ignoredClassNames[substr($className, strrpos($className, '\\') + 1)])) {
			return FALSE;
		}

			// Load classes from the FLOW3 package at a very early stage where
			// no packages have been registered yet:
		if ($this->packages === array() && substr($className, 0, 11) === 'TYPO3\FLOW3') {
			require(FLOW3_PATH_FLOW3 . 'Classes/' . str_replace('\\', '/', substr($className, 12)) . '.php');
			return TRUE;
		}

			// Loads any non-proxied class of registered packages:
		foreach ($this->packageNamespaces as $packageNamespace => $packageData) {
			if (substr($className, 0, $packageData['namespaceLength']) === $packageNamespace) {
				if ($this->considerTestsNamespace === TRUE && substr($className, $packageData['namespaceLength'] + 1, 16) === 'Tests\Functional') {
					$classPathAndFilename = $this->packages[str_replace('\\', '.', $packageNamespace)]->getPackagePath() . str_replace('\\', '/', substr($className, $packageData['namespaceLength'] + 1)) . '.php';
					if (file_exists($classPathAndFilename)) {
						require($classPathAndFilename);
						return TRUE;
					}
				} else {

						// The only reason using file_exists here is that Doctrine tries
						// out several combinations of annotation namespaces and thus also triggers
						// autoloading for non-existant classes in a valid package namespace
					$classPathAndFilename = $packageData['classesPath'] . str_replace('\\', '/', substr($className, $packageData['namespaceLength'])) . '.php';
					if (file_exists($classPathAndFilename)) {
						require ($classPathAndFilename);
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\FLOW3\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
		foreach ($packages as $package) {
			$this->packageNamespaces[$package->getPackageNamespace()] = array('namespaceLength' => strlen($package->getPackageNamespace()), 'classesPath' => $package->getClassesPath());
		}
	}

	/**
	 * Sets the flag which enables or disables autoloading support for functional
	 * test files.
	 *
	 * @param boolean $flag
	 * @return void
	 */
	public function setConsiderTestsNamespace($flag) {
		$this->considerTestsNamespace = $flag;
	}
}

?>