<?php
namespace TYPO3\FLOW3\Security\RequestPattern;

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
 * This class holds an controller object name pattern an decides, if a \TYPO3\FLOW3\MVC\Web\Request object matches against this pattern
 *
 * @FLOW3\Scope("prototype")
 */
class ControllerObjectName implements \TYPO3\FLOW3\Security\RequestPatternInterface {

	/**
	 * The preg_match() styled controller object name pattern
	 * @var string
	 */
	protected $controllerObjectNamePattern = '';

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		return TRUE;
	}

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets an controller object name pattern (preg_match() syntax)
	 *
	 * @param string $controllerObjectNamePattern The preg_match() styled controller object name pattern
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPattern($controllerObjectNamePattern) {
		$this->controllerObjectNamePattern = $controllerObjectNamePattern;
	}

	/**
	 * Matches a \TYPO3\FLOW3\MVC\RequestInterface against its set controller object name pattern rules
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		return (boolean)preg_match('/^' . str_replace('\\', '\\\\', $this->controllerObjectNamePattern) . '$/', $request->getControllerObjectName());
	}
}

?>