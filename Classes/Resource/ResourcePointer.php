<?php
namespace TYPO3\FLOW3\Resource;

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
 * Model describing a resource pointer
 *
 * @FLOW3\Scope("prototype")
 * @FLOW3\ValueObject
 */
class ResourcePointer {

	/**
	 * @var string
	 * @ORM\Id
	 */
	protected $hash;

	/**
	 * Constructs this resource pointer
	 *
	 */
	public function __construct($hash) {
		if (!is_string($hash) || strlen($hash) !== 40) {
			throw new \InvalidArgumentException('A valid sha1 hash must be passed to this constructor.', 1259748358);
		}
		$this->hash = $hash;
	}

	/**
	 * Returns the hash of this resource
	 *
	 * @return string A 40 character hexadecimal sha1 hash over the content of this resource
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Returns a string representation of this resource object.
	 *
	 * @return string The hash of this resource
	 */
	public function __toString() {
		return $this->hash;
	}
}

?>