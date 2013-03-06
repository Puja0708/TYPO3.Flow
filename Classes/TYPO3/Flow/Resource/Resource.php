<?php
namespace TYPO3\Flow\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * Model representing a resource
 *
 * @Flow\Entity
 */
class Resource {

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=100 })
	 * @ORM\Column(length=100)
	 */
	protected $filename = '';

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=100 })
	 * @ORM\Column(length=100)
	 */
	protected $fileExtension = '';

	/**
	 * SHA1 hash identifying the content attached to this resource
	 *
	 * @var string
	 * @ORM\Column(length=40)
	 */
	protected $hash;

	/**
	 * Name of a collection whose storage is used for storing this resource and whose
	 * target is used for publishing.
	 *
	 * @var string
	 */
	protected $collectionName = 'persistentResources';

	/**
	 * Returns the SHA1 of the content this Resource is related to
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->hash;
	}

	/**
	 * Returns a resource://<sha1> URI for use with file operations, …
	 *
	 * @return string
	 * @api
	 */
	public function getUri() {
		return 'resource://' . $this->hash;
	}

	/**
	 * Sets the filename
	 *
	 * @param string $filename
	 * @return void
	 * @api
	 */
	public function setFilename($filename) {
		$pathInfo = pathinfo($filename);
		if (isset($pathInfo['extension'])) {
			$this->fileExtension = strtolower($pathInfo['extension']);
		} else {
			$this->fileExtension = '';
		}
		$this->filename = $pathInfo['filename'];
		if ($this->fileExtension !== '') {
			$this->filename .= '.' . $this->fileExtension;
		}
	}

	/**
	 * Gets the filename
	 *
	 * @return string The filename
	 * @api
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Returns the file extension used for this resource
	 *
	 * @return string The file extension used for this file
	 * @api
	 */
	public function getFileExtension() {
		return $this->fileExtension;
	}

	/**
	 * Returns the mime type for this resource
	 *
	 * @return string The mime type
	 * @deprecated since 1.1.0
	 * @see getMediaType()
	 */
	public function getMimeType() {
		return $this->getMediaType();
	}

	/**
	 * Returns the Media Type for this resource
	 *
	 * @return string The IANA Media Type
	 * @api
	 */
	public function getMediaType() {
		return MediaTypes::getMediaTypeFromFilename('x.' . $this->getFileExtension());
	}

	/**
	 * Returns the sha1 hash of the content of this resource
	 *
	 * @return string The sha1 hash
	 * @api
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Sets the sha1 hash of the content of this resource
	 *
	 * @param string $hash The sha1 hash
	 * @return void
	 * @api
	 */
	public function setHash($hash) {
		if (strlen($hash) !== 40) {
			throw new \InvalidArgumentException('Specified invalid hash to setHash()', 1362564119);
		}
		$this->hash = $hash;
	}

	/**
	 * Sets the resource pointer
	 *
	 * Deprecated – use setHash() instead!
	 *
	 * @param \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 * @return void
	 * @deprecated since 2.1.0
	 * @see setHash()
	 */
	public function setResourcePointer(ResourcePointer $resourcePointer) {
		$this->hash = $resourcePointer->getHash();
	}

	/**
	 * Returns the resource pointer
	 *
	 * Deprecated – use getHash() instead!
	 *
	 * @return \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 * @api
	 * @deprecated since 2.1.0
	 */
	public function getResourcePointer() {
		return new ResourcePointer($this->hash);
	}

	/**
	 * Sets the name of the collection this resource should be part of
	 *
	 * @param string $collectionName Name of the collection
	 * @return void
	 * @api
	 */
	public function setCollectionName($collectionName) {
		$this->collectionName = $collectionName;
	}

	/**
	 * Returns the name of the collection this resource is part of
	 *
	 * @return string Name of the collection, for example "persistentResources"
	 * @api
	 */
	public function getCollectionName() {
		return $this->collectionName;
	}

}
?>
