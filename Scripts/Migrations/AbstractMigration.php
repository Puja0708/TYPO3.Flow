<?php
namespace TYPO3\FLOW3\Core\Migrations;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Core\Migrations\Tools;

/**
 * The base class for code migrations.
 */
abstract class AbstractMigration {

	const MAXIMUM_LINE_LENGTH = 79;

	/**
	 * @var Manager
	 */
	protected $migrationsManager;

	/**
	 * @var string
	 */
	protected $packageKey;

	/**
	 * @var array
	 */
	protected $packageData;

	/**
	 * @var array
	 */
	protected $operations = array('searchAndReplace' => array(), 'searchAndReplaceRegex' => array());

	/**
	 * @var array
	 */
	protected $notes = array();

	/**
	 * @var array
	 */
	protected $warnings = array();

	/**
	 * @param \TYPO3\FLOW3\Core\Migrations\Manager $manager
	 * @param string $packageKey
	 */
	public function __construct(\TYPO3\FLOW3\Core\Migrations\Manager $manager, $packageKey) {
		$this->migrationsManager = $manager;
		$this->packageKey = $packageKey;
	}

	/**
	 * Returns the package key this migration comes from.
	 *
	 * @return string
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * Returns the identifier of this migration, e.g. 'FLOW3-201201261636'.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->packageKey . '-' . substr(get_class($this), -12);
	}

	/**
	 * Anything that needs to be done in the migration when migrating
	 * into the "up" direction needs to go into this method.
	 *
	 * It will be called by the Manager upon migration.
	 *
	 * @return void
	 * @api
	 */
	abstract public function up();

	/**
	 * @param array $packageData
	 * @return void
	 */
	public function execute(array $packageData) {
		$this->packageData = $packageData;
		$this->applySearchAndReplaceOperations();
	}

	/**
	 * Will show all notes and warnings accumulated.
	 *
	 * @return void
	 */
	public function outputNotesAndWarnings() {
		foreach (array('notes', 'warnings') as $type) {
			if ($this->$type === array()) {
				continue;
			}

			$this->outputLine();
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			$this->outputLine('   ' . ucfirst($type));
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			foreach ($this->$type as $note) {
				$this->outputLine('  * ' . $this->wrapAndPrefix($note));
			}
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
		}
	}

	/**
	 * This can be used to show a note to the developer.
	 *
	 * If changes cannot be automated or something needs to be
	 * adjusted  manually for other reasons, leave a note for the
	 * developer. The notes will be shown together after migrations
	 * have been run.
	 *
	 * @param string $note
	 * @return void
	 * @see showWarning
	 * @api
	 */
	protected function showNote($note) {
		$this->notes[] = $note;
	}

	/**
	/**
	 * This can be used to show a warning to the developer.
	 *
	 * Similar to showNote, but the output is given a stronger
	 * emphasis. The warnings will be shown together after migrations
	 * have been run.
	 *
	 * @param string $warning
	 * @return void
	 * @see showNote
	 * @api
	 */
	protected function showWarning($warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Does a simple search and replace on all (textual) files. The filter array can be
	 * used to give file extensions to limit the operation to.
	 *
	 * @param string $search
	 * @param string $replacement
	 * @param array $filter
	 * @return void
	 * @api
	 */
	protected function searchAndReplace($search, $replacement, array $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplace'][] = array($search, $replacement, $filter);
	}

	/**
	 * Does a regex search and replace on all (textual) files. The filter array can be
	 * used to give file extensions to limit the operation to.
	 *
	 * The patterns are used as is, no quoting is done.
	 *
	 * @param string $search
	 * @param string $replacement
	 * @param array $filter
	 * @return void
	 * @api
	 */
	protected function searchAndReplaceRegex($search, $replacement, array $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplaceRegex'][] = array($search, $replacement, $filter);
	}

	/**
	 * Rename a class from $oldName to $newName.
	 *
	 * This expects fully qualified class names, so proper refactoring
	 * can be done.
	 *
	 * @param string $oldName
	 * @param string $newName
	 * @return void
	 * @throws \LogicException
	 */
	protected function renameClass($oldName, $newName) {
		throw new \LogicException('renameClass is not yet implemented, sorry!', 1335525001);
	}

	/**
	 * Rename a class method.
	 *
	 * This expects a fully qualified class name, so proper refactoring
	 * can be done.
	 *
	 * @param string $className the class that contains the method to be renamed
	 * @param string $oldMethodName the method to be renamed
	 * @param string $newMethodName the new method name
	 * @param boolean $withInheritance if true, also rename method on child classes
	 * @return void
	 * @throws \LogicException
	 */
	protected function renameMethod($className, $oldMethodName, $newMethodName, $withInheritance = TRUE) {
		throw new \LogicException('renameClass is not yet implemented, sorry!', 1335525001);
	}

	/**
	 * Applies all registered searchAndReplace and searchAndReplaceRegex operations.
	 *
	 * @return void
	 */
	protected function applySearchAndReplaceOperations() {
		$allPathsAndFilenames = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($this->packageData['path'], NULL, TRUE);
		foreach ($this->operations['searchAndReplace'] as $operation) {
			foreach ($allPathsAndFilenames as $pathAndFilename) {
				$pathInfo = pathinfo($pathAndFilename);
				if (!isset($pathInfo['filename'])) continue;
				if (strpos($pathAndFilename, 'Migrations/Code') !== FALSE) continue;

				if ($operation[2] !== array()) {
					if (!isset($pathInfo['extension']) || !in_array($pathInfo['extension'], $operation[2], TRUE)) {
						continue;
					}
				}
				Tools::searchAndReplace($operation[0], $operation[1], $pathAndFilename);
			}
		}
	}

	/**
	 * The given text is word-wrapped and each line after the first one is
	 * prefixed with $prefix.
	 *
	 * @param string $text
	 * @param string $prefix
	 * @return string
	 */
	protected function wrapAndPrefix($text, $prefix = '    ') {
		$text = explode(chr(10), wordwrap($text, self::MAXIMUM_LINE_LENGTH, chr(10), TRUE));
		return implode(PHP_EOL . $prefix, $text);
	}

	/**
	 * Outputs specified text to the console window and appends a line break.
	 *
	 * You can specify arguments that will be passed to the text via sprintf
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		echo $text . PHP_EOL;
	}

}

?>