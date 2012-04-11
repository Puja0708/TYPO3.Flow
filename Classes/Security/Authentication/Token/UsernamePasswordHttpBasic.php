<?php
namespace TYPO3\FLOW3\Security\Authentication\Token;

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
 * An authentication token used for simple username and password authentication via HTTP Basic Auth.
 *
 */
class UsernamePasswordHttpBasic extends UsernamePassword {

	/**
	 * Updates the username and password credentials from the HTTP authorization header.
	 * Sets the authentication status to AUTHENTICATION_NEEDED, if the header has been
	 * sent, to NO_CREDENTIALS_GIVEN if no authorization header was there.
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request The current request instance
	 * @return void
	 */
	public function updateCredentials(\TYPO3\FLOW3\Http\Request $request) {
		$authorizationHeader = $request->getHeaders()->get('Authorization');
		if (substr($authorizationHeader, 0, 5) === 'Basic') {
			$credentials = base64_decode(substr($authorizationHeader, 6));
			$this->credentials['username'] = substr($credentials, 0, strpos($credentials, ':'));
			$this->credentials['password'] = substr($credentials, strpos($credentials, ':') + 1);
			$this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
		} else {
			$this->credentials = array('username' => NULL, 'password' => NULL);
			$this->authenticationStatus = self::NO_CREDENTIALS_GIVEN;
		}
	}
}
?>
