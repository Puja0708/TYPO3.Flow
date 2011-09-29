<?php
namespace TYPO3\FLOW3\Security;

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
 * Contract for a request pattern.
 *
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 */
interface RequestPatternInterface {

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(\TYPO3\FLOW3\MVC\RequestInterface $request);

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern();

	/**
	 * Sets the pattern (match) configuration
	 *
	 * @param object $pattern The pattern (match) configuration
	 * @return void
	 */
	public function setPattern($pattern);

	/**
	 * Matches a \TYPO3\FLOW3\MVC\RequestInterface against its set pattern rules
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	public function matchRequest(\TYPO3\FLOW3\MVC\RequestInterface $request);
}

?>