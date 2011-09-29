<?php
namespace TYPO3\FLOW3\Security\Authorization\Resource;

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
 * An access restriction publisher that publishes .htaccess files to configure apache2 restrictions
 *
 * @scope singleton
 */
class Apache2AccessRestrictionPublisher implements \TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface {

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 * @inject
	 */
	protected $environment;

	/**
	 * Publishes an Apache2 .htaccess file which allows access to the given directory only for the current session remote ip
	 *
	 * @param string $path The path to publish the restrictions for
	 * @return void
	 */
	public function publishAccessRestrictionsForPath($path) {
		$content = "Deny from all\nAllow from " . $this->environment->getRemoteAddress();

		file_put_contents($path . '.htaccess', $content);
	}
}

?>
