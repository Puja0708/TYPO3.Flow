<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A simple value object for persistence tests
 *
 * @FLOW3\ValueObject
 * @ORM\Table(name="Persistence_TestValueObject")
 */
class TestValueObject {

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @param string $value The string value of this value object
	 */
	public function __construct($value) {
		$this->value = $value;
	}
}
?>