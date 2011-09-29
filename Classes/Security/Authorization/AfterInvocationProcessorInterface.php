<?php
namespace TYPO3\FLOW3\Security\Authorization;

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
 * Contract for an after invocation processor.
 *
 */
interface AfterInvocationProcessorInterface {

	/**
	 * Processes the given return object. May throw an security exception or filter the result depending on the current user rights.
	 * It is resolved and called automatically by the after invocation processor manager. The naming convention for after invocation processors is:
	 * [InterceptedClassName]_[InterceptedMethodName]AfterInvocationProcessor
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current securit context
	 * @param object $object The return object to be processed
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint of the returning method
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function process(\TYPO3\FLOW3\Security\Context $securityContext, $object, \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint);

	/**
	 * Returns TRUE if this after invocation processor can process return objects of the given classname
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision manager can decide on objects with the given classname
	 */
	public function supports($className);
}

?>