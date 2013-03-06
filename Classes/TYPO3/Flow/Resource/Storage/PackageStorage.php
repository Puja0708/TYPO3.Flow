<?php
namespace TYPO3\Flow\Resource\Storage;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;

/**
 * A resource storage which stores and retrieves resources from active Flow packages.
 */
class PackageStorage extends FileSystemStorage {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Initializes this resource storage
	 *
	 * @return void
	 */
	public function initializeObject() {
		// override the FileSystemStorage method because we don't need that here
	}

	/**
	 * Returns a list of all directories within this storage which match the given
	 * glob pattern.
	 *
	 * @param string $pattern A glob pattern, for example "Resources/Public/*"
	 * @return array An array of absolute directory paths, without ending slash
	 */
	public function getDirectories($pattern = NULL) {
		$directories = array();
		if ($pattern !== NULL) {
			if (strpos($pattern, '/') !== FALSE) {
				list($packageKeyPattern, $directoryPattern) = explode('/', $pattern, 2);
			} else {
				$packageKeyPattern = $pattern;
				$directoryPattern = '*';
			}
		}
		foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
			// TODO: filter packages
			if ($directoryPattern === '*') {
				$directories[] = $package->getPackagePath();
			} else {
				$directories = array_merge(glob($package->getPackagePath() . $directoryPattern, GLOB_ONLYDIR));
			}
		}
			// Transform paths to something like "Acme.Demo/Resources/Public":
		$storageName = $this->name;
		array_walk($directories, function(&$value, $key) use($storageName){
			list(, $value) = explode('/', str_replace(FLOW_PATH_PACKAGES, '', $value), 2);
			$value = $storageName . '://' . $value;
		});
		return $directories;
	}

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * The path and filename returned is always a regular, local path and filename.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource stored in this storage
	 * @return mixed A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResource(Resource $resource) {
		$pathAndFilename = $this->path . $resource->getHash();
		return (file_exists($pathAndFilename) ? $pathAndFilename : FALSE);
	}

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * The $relativePath must contain a package key as its first path segment,
	 * followed by the a path relative to that package.
	 *
	 * Example: "TYPO3.Flow/Resources/Public/Logo.png"
	 *
	 * The path and filename returned is always a regular, local path and filename.
	 *
	 * @param string $relativePath A relative path of this storage, first the package key, then the relative path
	 * @return mixed The full path and filename leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResourcePath($relativePath) {
		list($packageKey, $relativePath) = explode('/', $relativePath, 2);
		$package = $this->packageManager->getPackage($packageKey);
		return $package->getPackagePath() . $relativePath;
	}

}

?>