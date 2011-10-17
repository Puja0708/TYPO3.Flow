<?php
namespace TYPO3\FLOW3\MVC\Web\Routing;

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
 * An URI Builder
 *
 * @api
 * @FLOW3\Scope("prototype")
 */
class UriBuilder {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Arguments which have been used for building the last URI
	 * @var array
	 */
	protected $lastArguments = array();

	/**
	 * @var string
	 */
	protected $section = '';

	/**
	 * @var boolean
	 */
	protected $createAbsoluteUri = FALSE;

	/**
	 * @var boolean
	 */
	protected $addQueryString = FALSE;

	/**
	 * @var array
	 */
	protected $argumentsToBeExcludedFromQueryString = array();

	/**
	 * @var string
	 */
	protected $format = NULL;

	/**
	 * Injects the Router
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectRouter(\TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Injects the environment
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the current request and resets the UriBuilder
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Request $request
	 * @return void
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see reset()
	 */
	public function setRequest(\TYPO3\FLOW3\MVC\Web\Request $request) {
		$this->request = $request;
		$this->reset();
	}

	/**
	 * Gets the current request
	 *
	 * @return \TYPO3\FLOW3\MVC\Web\Request
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Additional query parameters.
	 * If you want to "prefix" arguments, you can pass in multidimensional arrays:
	 * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
	 *
	 * @param array $arguments
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * If specified, adds a given HTML anchor to the URI (#...)
	 *
	 * @param string $section
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setSection($section) {
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Specifies the format of the target (e.g. "html" or "xml")
	 *
	 * @param string $format (e.g. "html" or "xml"), will be transformed to lowercase!
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setFormat($format) {
		$this->format = strtolower($format);
		return $this;
	}

	/**
	 * @return string
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * If set, the URI is prepended with the current base URI. Defaults to FALSE.
	 *
	 * @param boolean $createAbsoluteUri
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setCreateAbsoluteUri($createAbsoluteUri) {
		$this->createAbsoluteUri = (boolean)$createAbsoluteUri;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCreateAbsoluteUri() {
		return $this->createAbsoluteUri;
	}

	/**
	 * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
	 *
	 * @param boolean $addQueryString
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setAddQueryString($addQueryString) {
		$this->addQueryString = (boolean)$addQueryString;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getAddQueryString() {
		return $this->addQueryString;
	}

	/**
	 * A list of arguments to be excluded from the query parameters
	 * Only active if addQueryString is set
	 *
	 * @param array $argumentsToBeExcludedFromQueryString
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString) {
		$this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentsToBeExcludedFromQueryString() {
		return $this->argumentsToBeExcludedFromQueryString;
	}

	/**
	 * Returns the arguments being used for the last URI being built.
	 * This is only set after build() / uriFor() has been called.
	 *
	 * @return array The last arguments
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getLastArguments() {
		return $this->lastArguments;
	}

	/**
	 * Resets all UriBuilder options to their default value.
	 * Note: This won't reset the Request that is attached to this UriBuilder (@see setRequest())
	 *
	 * @return \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function reset() {
		$this->arguments = array();
		$this->section = '';
		$this->format = NULL;
		$this->createAbsoluteUri = FALSE;
		$this->addQueryString = FALSE;
		$this->argumentsToBeExcludedFromQueryString = array();

		return $this;
	}

	/**
	 * Creates an URI used for linking to an Controller action.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $controllerArguments Additional query parameters. Will be merged with $this->arguments.
	 * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
	 * @param string $packageKey Name of the target package. If not set, current Package is used.
	 * @param string $subPackageKey Name of the target SubPackage. If not set, current SubPackage is used.
	 * @return string the rendered URI
	 * @api
	 * @see build()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriFor($actionName = NULL, $controllerArguments = array(), $controllerName = NULL, $packageKey = NULL, $subPackageKey = NULL) {
		if ($actionName !== NULL) {
			$controllerArguments['@action'] = strtolower($actionName);
		}
		if ($controllerName !== NULL) {
			$controllerArguments['@controller'] = strtolower($controllerName);
		} else {
			$controllerArguments['@controller'] = strtolower($this->request->getControllerName());
		}
		if ($packageKey === NULL && $subPackageKey === NULL) {
			$subPackageKey = $this->request->getControllerSubpackageKey();
		}
		if ($packageKey === NULL) {
			$packageKey = $this->request->getControllerPackageKey();
		}
		$controllerArguments['@package'] = strtolower($packageKey);
		if (strlen($subPackageKey) > 0) {
			$controllerArguments['@subpackage'] = strtolower($subPackageKey);
		}
		if ($this->format !== NULL && $this->format !== '') {
			$controllerArguments['@format'] = $this->format;
		}

		$controllerArguments = $this->addNamespaceToArguments($controllerArguments);
		return $this->build($controllerArguments);
	}

	/**
	 * Adds the argument namespace of the current request to the specified arguments.
	 * This happens recursively iterating through the nested requests in case of a subrequest.
	 * For example if this is executed inside a widget subrequest in a plugin subrequest, the result would be:
	 * array(
	 *   'pluginRequestNamespace' => array(
	 *     'widgetRequestNamespace => $arguments
	 *    )
	 * )
	 *
	 * @param array $arguments arguments
	 * @return array arguments with namespace
	 */
	protected function addNamespaceToArguments(array $arguments) {
		$currentRequest = $this->request;
		while($currentRequest instanceof \TYPO3\FLOW3\MVC\Web\SubRequest) {
			$argumentNamespace = $currentRequest->getArgumentNamespace();
			if ($argumentNamespace !== '') {
				$arguments = array($argumentNamespace => $arguments);
			}
			$currentRequest = $currentRequest->getParentRequest();
		}
		return $arguments;
	}

	/**
	 * Builds the URI
	 *
	 * @param array $arguments optional URI arguments. Will be merged with $this->arguments with precedence to $arguments
	 * @return string The URI
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function build(array $arguments = array()) {
		$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->arguments, $arguments);
		$this->mergeArgumentsWithRequestArguments($arguments);

		$uri = $this->router->resolve($arguments);
		$this->lastArguments = $arguments;
		if (!$this->environment->isRewriteEnabled()) {
			$uri = 'index.php/' . $uri;
		}
		if ($this->createAbsoluteUri === TRUE) {
			$uri = $this->request->getBaseUri() . $uri;
		}
		if ($this->section !== '') {
			$uri .= '#' . $this->section;
		}
		return $uri;
	}

	/**
	 * Merges specified arguments with arguments from request.
	 * If $this->request is no SubRequest, request arguments will only be merged if $this->addQueryString is set.
	 * Otherwise all request arguments except for the ones prefixed with the current request argument namespace will
	 * be merged. Additionally special arguments (PackageKey, SubpackageKey, ControllerName & Action) are merged.
	 *
	 * Note: values of $arguments always overrule request arguments!
	 *
	 * @param array $arguments
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function mergeArgumentsWithRequestArguments(array &$arguments) {
		$requestArguments = array();
		if ($this->request instanceof \TYPO3\FLOW3\MVC\Web\SubRequest) {
			$rootRequest = $this->request->getRootRequest();
			$requestArguments = $rootRequest->getArguments();
				// remove all arguments of the current SubRequest
			if ($this->request->getArgumentNamespace() !== '') {
				unset($requestArguments[$this->request->getArgumentNamespace()]);
			}

				// merge special arguments (package, subpackage, controller & action) from root request
			$rootRequestPackageKey = $rootRequest->getControllerPackageKey();
			if (!empty($rootRequestPackageKey)) {
				$requestArguments['@package'] = $rootRequestPackageKey;
			}
			$rootRequestSubpackageKey = $rootRequest->getControllerSubpackageKey();
			if (!empty($rootRequestSubpackageKey)) {
				$requestArguments['@subpackage'] = $rootRequestSubpackageKey;
			}
			$rootRequestControllerName = $rootRequest->getControllerName();
			if (!empty($rootRequestControllerName)) {
				$requestArguments['@controller'] = $rootRequestControllerName;
			}
			$rootRequestActionName = $rootRequest->getControllerActionName();
			if (!empty($rootRequestActionName)) {
				$requestArguments['@action'] = $rootRequestActionName;
			}

		} elseif ($this->addQueryString === TRUE) {
			$requestArguments = $this->request->getArguments();
		}

		if (count($requestArguments) === 0) {
			return;
		}

		foreach($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
			unset($requestArguments[$argumentToBeExcluded]);
		}

		$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
	}

}

?>