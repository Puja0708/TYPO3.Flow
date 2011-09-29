<?php
namespace TYPO3\FLOW3\Configuration\Source;

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
 * Configuration source based on YAML files
 *
 * @scope singleton
 * @api
 */
class YamlSource implements \TYPO3\FLOW3\Configuration\Source\SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load, excluding the file extension (ie. ".yaml")
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($pathAndFilename) {
		if (file_exists($pathAndFilename . '.yaml')) {
			try {
				$configuration = \TYPO3\FLOW3\Configuration\Source\YamlParser::loadFile($pathAndFilename . '.yaml');
			} catch (\TYPO3\FLOW3\Error\Exception $exception) {
				throw new \TYPO3\FLOW3\Configuration\Exception\ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '.yaml". Error message: ' . $exception->getMessage(), 1232014321);
			}
		} else {
			$configuration = array();
		}
		return $configuration;
	}

	/**
	 * Save the specified configuration array to the given file in YAML format.
	 *
	 * @param string $pathAndFilename Full path and file name of the file to write to, excluding the dot and file extension (i.e. ".yaml")
	 * @param array $configuration The configuration to save
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function save($pathAndFilename, array $configuration) {
		$header = '';
		if (file_exists($pathAndFilename . '.yaml')) {
			$header = $this->getHeaderFromFile($pathAndFilename . '.yaml');
		}
		$yaml = \TYPO3\FLOW3\Configuration\Source\YamlParser::dump($configuration);
		file_put_contents($pathAndFilename . '.yaml', $header . PHP_EOL . $yaml);
	}

	/**
	 * Read the header part from the given file. That means, every line
	 * until the first non comment line is found.
	 *
	 * @param string $pathAndFilename
	 * @return string The header of the given YAML file
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	protected function getHeaderFromFile($pathAndFilename) {
		$header = '';
		$line = '';
		$fileHandle = fopen($pathAndFilename, 'r');
		while ($line = fgets($fileHandle)) {
			if (preg_match('/^#/', $line)) {
				$header .= $line;
			} else {
				break;
			}
		}
		fclose($fileHandle);
		return $header;
	}
}
?>