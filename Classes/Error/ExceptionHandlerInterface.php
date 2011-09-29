<?php
namespace TYPO3\FLOW3\Error;

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
 * Contract for an exception handler
 *
 */
interface ExceptionHandlerInterface {

	/**
	 * Handles the given exception
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception);

}
?>