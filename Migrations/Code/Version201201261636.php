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

/**
 *
 */
class Version201201261636 extends AbstractMigration {

	public function up() {
		$this->searchAndReplace('TYPO3\FLOW3\MVC\CLI', 'TYPO3\FLOW3\Cli');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Routing', 'TYPO3\FLOW3\Mvc\Routing');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Request', 'TYPO3\FLOW3\Mvc\ActionRequest');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Response', 'TYPO3\FLOW3\Http\Response');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\SubRequest', 'TYPO3\FLOW3\Mvc\ActionRequest');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\SubResponse', 'TYPO3\FLOW3\Http\Response');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Controller\CommandController', 'TYPO3\FLOW3\Cli\CommandController');
		$this->searchAndReplace('TYPO3\FLOW3\Property\DataType\Uri', 'TYPO3\FLOW3\Http\Uri');
		$this->searchAndReplace('TYPO3\FLOW3\AOP', 'TYPO3\FLOW3\Aop');
		$this->searchAndReplace('TYPO3\FLOW3\MVC', 'TYPO3\FLOW3\Mvc');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\RequestInterface', 'TYPO3\FLOW3\Http\Request');
		$this->searchAndReplace('\AOP', '\Aop');
		$this->searchAndReplace('\MVC', '\Mvc');

		$this->searchAndReplace('->getRootRequest()', '->getMainRequest()');
		$this->searchAndReplace('$this->controllerContext->getRequest()->getBaseUri()', '$this->controllerContext->getRequest()->getHttpRequest()->getBaseUri()');

		$this->showNote('\TYPO3\FLOW3\MVC\Web\RequestBuilder does not exist anymore. If you need to create requests, do "new ActionRequest($parentRequest)".');
		$this->showNote('\TYPO3\FLOW3\MVC\Web\SubRequestBuilder does not exist anymore. If you need to create sub requests, do "new ActionRequest($parentRequest)".');
		$this->showNote('\TYPO3\FLOW3\MVC\RequestInterface has been removed, use \TYPO3\FLOW3\Http\Request instead - e.g. if you implemented your own token.');
		$this->showNote('$supportedRequestTypes are not needed anymore in a controller.');
		$this->showNote('In Settings.yaml "providerClass" is deprecated, use "provider" instead. Also change entryPoint configuration from:
entryPoint:
  WebRedirect:
    uri: login.html
to
entryPoint: \'WebRedirect\'
entryPointOptions:
  uri: \'login.html\'');

		$this->showWarning('Class names in pointcut expressions might not be fully qualified, check manually if needed.');
	}

}

?>