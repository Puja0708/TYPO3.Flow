<?php
namespace TYPO3\FLOW3\Tests\Persistence\Fixture\Repository;

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
 * A repository claiming responsibility for a model that cannot be matched
 * to it via naming conventions.
 *
 * @FLOW3\Scope("singleton")
 */
class NonstandardEntityRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\FLOW3\Tests\Persistence\Fixture\Model\Entity';

}

?>