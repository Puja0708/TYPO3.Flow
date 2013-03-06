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

use TYPO3\Flow\Resource\Resource;

/**
 * Interface for a resource storage
 */
interface StorageInterface {

	/**
	 * Returns the instance name of this storage
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Imports a resource (file) as specified in the given upload info array as a
	 * persistent resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @return mixed A resource object representing the imported resource or an error message if an error occurred
	 */
	public function importUploadedResource(array $uploadInfo);

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage. Most often this URI is private and not to be shared
	 * with the pubblic.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource stored in this storage
	 * @return mixed A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResource(Resource $resource);

	/**
	 * Returns a URI which can be used internally to open / copy the given resource
	 * stored in this storage. Most often this URI is private and not to be shared
	 * with the pubblic.
	 *
	 * @param string $relativePath A path relative to the storage root. This path might follow certain conventions defined by the concrete storage implementation.
	 * @return mixed A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getPrivateUriByResourcePath($relativePath);

}
?>