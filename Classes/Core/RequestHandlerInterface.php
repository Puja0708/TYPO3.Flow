<?php
namespace TYPO3\FLOW3\Core;

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
 * The interface for a request handler
 *
 * @api
 */
interface RequestHandlerInterface {

	/**
	 * Handles a raw request
	 *
	 * @return void
	 * @api
	 */
	public function handleRequest();

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return mixed TRUE or an integer > 0 if it can handle the request, otherwise FALSE or an integer < 0
	 * @api
	 */
	public function canHandleRequest();

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request. An integer > 0 means "I want to handle this request" where
	 * "100" is default. "0" means "I am a fallback solution".
	 *
	 * @return integer The priority of the request handler
	 * @api
	 */
	public function getPriority();

	/**
	 * Returns the top level request built by the request handler.
	 *
	 * In most cases the dispatcher or other parts of the request-response chain
	 * should be preferred for retrieving the current request, because sub requests
	 * or simulated requests are built later in the process.
	 *
	 * If, however, the original top level request is wanted, this is the right
	 * method for getting it.
	 *
	 * @return \TYPO3\FLOW3\MVC\RequestInterface The originally built web request
	 * @api
	 */
	public function getRequest();

}

?>