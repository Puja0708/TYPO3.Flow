<?php
namespace TYPO3\Flow\Resource\Publishing;

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

/**
 * NOTE: Although this class never belonged to the public API, the method
 *       getPersistentResourceWebUri() has been used in various packages.
 *       In order to keep backwards compatibility, we decided to leave this class
 *       containing the one method in 2.x.x version of Flow and mark it as deprecated.
 *
 *       Please make sure to use the new ResourceManager API instead!
 *
 * @Flow\Scope("singleton")
 * @deprecated since 2.1.0
 */
class ResourcePublisher {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * Returns the URI pointing to the published persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @deprecated since 2.1.0
	 */
	public function getPersistentResourceWebUri(Resource $resource) {
		return $this->resourceManager->getPublicPersistentResourceUri($resource);
	}
}

?>