<?php
namespace TYPO3\FLOW3\AOP;

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
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 * @FLOW3\Scope("prototype")
 */
class Advisor {

	/**
	 * The advisor's advice
	 * @var \TYPO3\FLOW3\AOP\Advice\AdviceInterface
	 */
	protected $advice;

	/**
	 * The pointcut for the advice
	 * @var \TYPO3\FLOW3\AOP\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param \TYPO3\FLOW3\AOP\Advice\AdviceInterface $advice The advice to weave in
	 * @param \TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut The pointcut where the advice should be inserted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\TYPO3\FLOW3\AOP\Advice\AdviceInterface $advice, \TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut) {
		$this->advice = $advice;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the advisor's advice
	 *
	 * @return \TYPO3\FLOW3\AOP\Advice\AdviceInterface The advice
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvice() {
		return $this->advice;
	}

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return \TYPO3\FLOW3\AOP\Pointcut\Pointcut The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}
}
?>