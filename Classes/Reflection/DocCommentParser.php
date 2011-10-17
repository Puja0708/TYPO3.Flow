<?php
namespace TYPO3\FLOW3\Reflection;

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
 * A little parser which creates tag objects from doc comments
 *
 * @FLOW3\Scope("singleton")
 */
class DocCommentParser {

	/**
	 * The description as found in the doc comment
	 * @var string
	 */
	protected $description = '';

	/**
	 * An array of tag names and their values (multiple values are possible)
	 * @var array
	 */
	protected $tags = array();

	/**
	 * Parses the given doc comment and saves the result (description and
	 * tags) in the parser's object. They can be retrieved by the
	 * getTags() getTagValues() and getDescription() methods.
	 *
	 * @param string $docComment A doc comment as returned by the reflection getDocComment() method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDocComment($docComment) {
		$this->description = '';
		$this->tags = array();

		$lines = explode(chr(10), $docComment);
		foreach ($lines as $line) {
			if (strlen($line) > 0 && strpos($line, '* @') !== FALSE) {
				$this->parseTag(substr($line, strpos($line, '@')));
			} else if (count($this->tags) === 0) {
				$this->description .= preg_replace('/\s*\\/?[\\\\*]*(.*)$/', '$1', $line) . chr(10);
			}
		}
		$this->description = trim($this->description);
	}

	/**
	 * Returns the tags which have been previously parsed
	 *
	 * @return array Array of tag names and their (multiple) values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->tags;
	}

	/**
	 * Returns the values of the specified tag. The doc comment
	 * must be parsed with parseDocComment() before tags are
	 * available.
	 *
	 * @param string $tagName The tag name to retrieve the values for
	 * @return array The tag's values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tagName) {
		if (!$this->isTaggedWith($tagName)) throw new \TYPO3\FLOW3\Reflection\Exception('Tag "' . $tagName . '" does not exist.', 1169128255);
		return $this->tags[$tagName];
	}

	/**
	 * Checks if a tag with the given name exists
	 *
	 * @param string $tagName The tag name to check for
	 * @return boolean TRUE the tag exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isTaggedWith($tagName) {
		return (isset($this->tags[$tagName]));
	}

	/**
	 * Returns the description which has been previously parsed
	 *
	 * @return string The description which has been parsed
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Parses a line of a doc comment for a tag and its value.
	 * The result is stored in the internal tags array.
	 *
	 * @param string $line A line of a doc comment which starts with an @-sign
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseTag($line) {
		$tagAndValue = array();
		if (preg_match('/@[A-Za-z0-9\\\\]+\\\\([A-Za-z0-9]+)(?:\\((.*)\\))?$/', $line, $tagAndValue) === 0) {
			$tagAndValue = preg_split('/\s/', $line, 2);
		} else {
			array_shift($tagAndValue);
		}
		$tag = strtolower(trim($tagAndValue[0], '@'));
		if (count($tagAndValue) > 1) {
			$this->tags[$tag][] = trim($tagAndValue[1], ' "');
		} else {
			$this->tags[$tag] = array();
		}
	}
}

?>