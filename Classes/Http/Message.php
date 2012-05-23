<?php
namespace TYPO3\FLOW3\Http;

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
use TYPO3\FLOW3\Mvc\ActionRequest;
use TYPO3\FLOW3\Utility\Arrays;

/**
 * Represents a HTTP message
 *
 * @api
 */
class Message {

	/**
	 * @var \TYPO3\FLOW3\Http\Headers
	 */
	protected $headers;

	/**
	 * Entity body of this message
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * @var string
	 */
	protected $charset = 'UTF-8';

	/**
	 *
	 */
	public function __construct() {
		$this->headers = new Headers();
	}

	/**
	 * Returns the HTTP headers of this request
	 *
	 * @return \TYPO3\FLOW3\Http\Headers
	 * @api
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Returns the value(s) of the specified header
	 *
	 * If one such header exists, the value is returned as a single string.
	 * If multiple headers of that name exist, the values are returned as an array.
	 * If no such header exists, NULL is returned.
	 *
	 * @param string $name Name of the header
	 * @return array|string An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
	 * @api
	 */
	public function getHeader($name) {
		return $this->headers->get($name);
	}

	/**
	 * Checks if the specified header exists.
	 *
	 * @param string $name Name of the HTTP header
	 * @return boolean
	 * @api
	 */
	public function hasHeader($name) {
		return $this->headers->has($name);
	}

	/**
	 * Sets the specified HTTP header
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param array|string|\DateTime $values An array of values or a single value for the specified header field
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @api
	 */
	public function setHeader($name, $values, $replaceExistingHeader = TRUE) {
		switch ($name) {
			case 'Content-Type' :
				if (stripos($values, 'charset') === FALSE && stripos($values, 'text/') === 0) {
					$values .= '; charset=' . $this->charset;
				}
			break;
		}

		$this->headers->set($name, $values, $replaceExistingHeader);
	}

	/**
	 * Explicitly sets the content of the message body
	 *
	 * @param string $content The body content
	 * @return void
	 * @api
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Returns the content of the message body
	 *
	 * @return string The response content
	 * @api
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Sets the character set for this message.
	 *
	 * If the content type of this message is a text/* media type, the character
	 * set in the respective Content-Type header will be updated by this method.
	 *
	 * @param string $charset A valid IANA character set identifier
	 * @return void
	 * @see http://www.iana.org/assignments/character-sets
	 * @api
	 */
	public function setCharset($charset) {
		$this->charset = $charset;

		if ($this->headers->has('Content-Type')) {
			$contentType = $this->headers->get('Content-Type');
			if (stripos($contentType, 'text/') === 0) {
				$matches = array();
				if (preg_match('/(?P<contenttype>.*); ?charset[^;]+(?P<extra>;.*)?/iu', $contentType, $matches)) {
					$contentType = $matches['contenttype'];
				}
				$contentType .= '; charset=' . $this->charset . (isset($matches['extra']) ? $matches['extra'] : '');
				$this->setHeader('Content-Type', $contentType, TRUE);
			}
		}
	}

	/**
	 * Returns the character set of this response.
	 *
	 * Note that the default character in FLOW3 is UTF-8.
	 *
	 * @return string An IANA character set identifier
	 * @api
	 */
	public function getCharset() {
		return $this->charset;
	}

}

?>