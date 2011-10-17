<?php
namespace TYPO3\FLOW3\Package\Documentation;

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
 * Documentation format of a documentation
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class Format {

	/**
	 * @var string
	 */
	protected $formatName;

	/**
	 * Absolute path to the documentation format
	 * @var string
	 */
	protected $formatPath;

	/**
	 * Constructor
	 *
	 * @param string $formatName Name of the documentation format
	 * @param string $formatPath Absolute path to the documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($formatName, $formatPath) {
		$this->formatName = $formatName;
		$this->formatPath = $formatPath;
	}

	/**
	 * Get the name of this documentation format
	 *
	 * @return string The name of this documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getFormatName() {
		return $this->formatName;
	}

	/**
	 * Get the full path to the directory of this documentation format
	 *
	 * @return string Path to the directory of this documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getFormatPath() {
		return $this->formatPath;
	}

	/**
	 * Returns the available languages for this documentation format
	 *
	 * @return array Array of string language codes
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getAvailableLanguages() {
		$languages = array();

		$languagesDirectoryIterator = new \DirectoryIterator($this->formatPath);
		$languagesDirectoryIterator->rewind();
		while ($languagesDirectoryIterator->valid()) {
			$filename = $languagesDirectoryIterator->getFilename();
			if ($filename[0] != '.' && $languagesDirectoryIterator->isDir()) {
				$language = $filename;
				$languages[] = $language;
			}
			$languagesDirectoryIterator->next();
		}

		return $languages;
	}
}
?>