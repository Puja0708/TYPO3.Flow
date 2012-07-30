<?php
namespace TYPO3\FLOW3\Http;

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
use TYPO3\FLOW3\Mvc\ActionRequest;
use TYPO3\FLOW3\Utility\Arrays;

/**
 * Represents a HTTP request
 *
 * @api
 * @FLOW3\Proxy(false)
 */
class Request extends Message {

	const PATTERN_SPLITMEDIARANGE = '/^(?P<type>(?:\*|[\.!#%&\'\`\^~\$\*\+\-\|\w]+))\/(?P<subtype>(?:\*|[\.!#%&\'\`\^~\$\*\+\-\|\w]+))(?P<parameters>.*)$/i';
	const PATTERN_SPLITMEDIATYPE = '/^(?P<type>(?:[\.!#%&\'\`\^~\$\*\+\-\|\w]+))\/(?P<subtype>(?:[\.!#%&\'\`\^~\$\*\+\-\|\w]+))(?P<parameters>.*)$/i';

	/**
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $uri;

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $baseUri;

	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @var array<\TYPO3\FLOW3\Http\Cookie>
	 */
	protected $cookies;

	/**
	 * Data similar to that which is typically provided by $_SERVER
	 *
	 * @var array
	 */
	protected $server;

	/**
	 * The "http" settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * URI for the "input" stream wrapper which can be modified for testing purposes
	 *
	 * @var string
	 */
	protected $inputStreamUri = 'php://input';

	/**
	 * Constructs a new Request object based on the given environment data.
	 *
	 * @param array $get Data similar to that which is typically provided by $_GET
	 * @param array $post Data similar to that which is typically provided by $_POST
	 * @param array $cookie Data similar to that which is typically provided by $_COOKIE
	 * @param array $files Data similar to that which is typically provided by $_FILES
	 * @param array $server Data similar to that which is typically provided by $_SERVER
	 * @see create()
	 * @see createFromEnvironment()
	 * @api
	 */
	public function __construct(array $get, array $post, array $cookie, array $files, array $server) {
		$this->headers = Headers::createFromServer($server);
		$this->setMethod(isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET');
		$protocol = (isset($server['SSL_SESSION_ID']) || (isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || strcmp($server['HTTPS'], '1') === 0))) ? 'https' : 'http';
		$this->uri = new Uri($protocol . '://' . (isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : 'localhost') . str_replace('/index.php' , '', (isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/')));
		$this->server = $server;
		$this->arguments = $this->buildUnifiedArguments($get, $post, $files);
	}

	/**
	 * Creates a new Request object from the given data.
	 *
	 * @param \TYPO3\FLOW3\Http\Uri $uri The request URI
	 * @param string $method Request method, for example "GET"
	 * @param array $arguments
	 * @param array $cookies
	 * @param array $files
	 * @param array $server
	 * @return \TYPO3\FLOW3\Http\Request
	 * @throws \InvalidArgumentException
	 * @api
	 */
	static public function create(Uri $uri, $method = 'GET', array $arguments = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		if (!in_array($method, array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'))) {
			throw new \InvalidArgumentException(sprintf('Invalid method "%s".', $method), 1326706916);
		}

		$get = $uri->getArguments();
		$post = $arguments;

		$isDefaultPort = $uri->getScheme() === 'https' ? ($uri->getPort() === 443) : ($uri->getPort() === 80);

		$defaultServerEnvironment = array(
			'HTTP_USER_AGENT' => 'FLOW3/' . FLOW3_VERSION_BRANCH . '.x',
			'HTTP_HOST' => $uri->getHost() . ($isDefaultPort !== TRUE ? ':' . $uri->getPort() : ''),
			'SERVER_NAME' => $uri->getHost(),
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => $uri->getPort() ?: 80,
			'REMOTE_ADDR' => '127.0.0.1',
			'SCRIPT_FILENAME' => FLOW3_PATH_WEB . 'index.php',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		if ($uri->getScheme() === 'https') {
			$defaultServerEnvironment['HTTPS'] = 'on';
			$defaultServerEnvironment['SERVER_PORT'] = $uri->getPort() ?: 443;
		}

		if (in_array($method, array('POST', 'PUT', 'DELETE'))) {
			$defaultServerEnvironment['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		}

		$query = $uri->getQuery();
		$fragment = $uri->getFragment();
		$overrideValues = array(
			'REQUEST_URI' => $uri->getPath() . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : ''),
			'REQUEST_METHOD' => $method,
			'QUERY_STRING' => $query
		);
		$server = array_replace($defaultServerEnvironment, $server, $overrideValues);

		return new static($get, $post, $cookies, $files, $server);
	}

	/**
	 * Considers the environment information found in PHP's superglobals and FLOW3's
	 * environment configuration and creates a new instance of this Request class
	 * matching that data.
	 *
	 * @return \TYPO3\FLOW3\Http\Request
	 * @api
	 */
	static public function createFromEnvironment() {
		return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
	}

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Creates a new Action Request request as a sub request to this HTTP request.
	 * Maps the arguments of this request to the new Action Request.
	 *
	 * @return \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	public function createActionRequest() {
		$actionRequest = new ActionRequest($this);
		$actionRequest->setArguments($this->arguments);
		return $actionRequest;
	}

	/**
	 * Returns the request URI
	 *
	 * @return \TYPO3\FLOW3\Http\Uri
	 * @api
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * Returns the detected base URI
	 *
	 * @return \TYPO3\FLOW3\Http\Uri
	 * @api
	 */
	public function getBaseUri() {
		if ($this->baseUri === NULL) {
			$this->detectBaseUri();
		}
		return $this->baseUri;
	}

	/**
	 * Indicates if this request has been received through a secure channel.
	 *
	 * @return boolean
	 * @api
	 */
	public function isSecure() {
		return ($this->uri->getScheme() === 'https' || $this->headers->has('X-Forwarded-Proto'));
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method The request method, for example "GET".
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setMethod($method) {
		if (!in_array($method, array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'))) {
			throw new \InvalidArgumentException(sprintf('Invalid method "%s".', $method), 1326445656);
		}
		$this->method = $method;
	}

	/**
	 * Returns the request method
	 *
	 * @return string The request method
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Tells if the request method is "safe", that is, it is expected to not take any
	 * other action than retrieval. This should the case with "GET" and "HEAD" requests.
	 *
	 * @return boolean
	 * @api
	 */
	public function isMethodSafe() {
		return (in_array($this->method, array('GET', 'HEAD')));
	}

	/**
	 * Returns the unified arguments of this request.
	 *
	 * GET, POST and PUT arguments, as well es uploaded files, are merged into a whole
	 * array of arguments.
	 *
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Checks if an argument of the given name exists in this request.
	 * Applies to GET, POST and PUT arguments similarly.
	 *
	 * @param string $name Name of the argument
	 * @return boolean
	 * @api
	 */
	public function hasArgument($name) {
		return isset($this->arguments[$name]);
	}

	/**
	 * Returns the value of the specified GET / POST / PUT argument.
	 *
	 * @param string $name Name of the argument
	 * @return mixed Value of the specified argument or NULL if it does not exist
	 * @api
	 */
	public function getArgument($name) {
		return (isset($this->arguments[$name]) ? $this->arguments[$name] : NULL);
	}

	/**
	 * Explicitly sets the content of the request body
	 *
	 * @param string $content The body content, for example arguments of a PUT request
	 * @return void
	 * @api
	 */
	public function setContent($content) {
		parent::setContent($content);
		$this->arguments = $this->buildUnifiedArguments($this->arguments, array(), array());
	}

	/**
	 * Returns the content of the request body
	 *
	 * @param boolean $asResource If set, the content is returned as a resource pointing to PHP's input stream
	 * @return string|resource
	 * @api
	 * @throws Exception
	 */
	public function getContent($asResource = FALSE) {
		if ($asResource === TRUE) {
			if ($this->content !== NULL) {
				throw new Exception('Cannot return request content as resource because it has already been retrieved.', 1332942478);
			}
			$this->content = '';
			return fopen($this->inputStreamUri, 'rb');
		}

		if ($this->content === NULL) {
			$this->content = file_get_contents($this->inputStreamUri);
		}
		return $this->content;
	}

	/**
	 * Returns the best guess of the client's IP address.
	 *
	 * Note that, depending on the actual source used, IP addresses can be spoofed
	 * and may not be reliable. Although several kinds of proxy headers are taken into
	 * account, certain combinations of ISPs and proxies might still produce wrong
	 * results.
	 *
	 * Don't rely on the client IP address as the only security measure!
	 *
	 * @return string The client's IP address
	 * @api
	 */
	public function getClientIpAddress() {
		$serverFields = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED');
		foreach ($serverFields as $field) {
			if (empty($this->server[$field])) {
				continue;
			}
			$length = strpos($this->server[$field], ',');
			$ipAddress = trim(($length === FALSE) ? $this->server[$field] : substr($this->server[$field], 0, $length));
			if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE) !== FALSE) {
				return $ipAddress;
			}
		}
		return (isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : NULL);
	}

	/**
	 * Returns an list of IANA media types defined in the Accept header.
	 *
	 * The list is ordered by user preference, after evaluating the Quality Values
	 * specified in the header field value. First items in the list are the most
	 * preferred.
	 *
	 * If no Accept header is present, the media type representing "any" media type
	 * is returned.
	 *
	 * @return array A list of media types and sub types
	 * @api
	 */
	public function getAcceptedMediaTypes() {
		$rawValues = $this->headers->get('Accept');
		if (empty($rawValues)) {
			return array('*/*');
		}
		$acceptedMediaTypes = self::parseContentNegotiationQualityValues($rawValues);
		return $acceptedMediaTypes;
	}

	/**
	 * Returns the best fitting IANA media type after applying the content negotiation
	 * rules on a possible Accept header.
	 *
	 * @param array $supportedMediaTypes A list of media types which are supported by the application / controller
	 * @param boolean $trim If TRUE, only the type/subtype of the media type is returned. If FALSE, the full original media type string is returned.
	 * @return string The media type and sub type which matched, NULL if none matched
	 * @api
	 */
	public function getNegotiatedMediaType(array $supportedMediaTypes, $trim = TRUE) {
		$negotiatedMediaType = NULL;
		$acceptedMediaTypes = $this->getAcceptedMediaTypes();
		foreach ($acceptedMediaTypes as $acceptedMediaType) {
			foreach ($supportedMediaTypes as $supportedMediaType) {
				if (self::mediaRangeMatches($acceptedMediaType, $supportedMediaType)) {
					$negotiatedMediaType = $supportedMediaType;
					break 2;
				}
			}
		}
		return ($trim ? self::trimMediaType($negotiatedMediaType) : $negotiatedMediaType);
	}

	/**
	 * Returns the relative path (ie. relative to the web root) and name of the
	 * script as it was accessed through the webserver.
	 *
	 * @return string Relative path and name of the PHP script as accessed through the web
	 * @api
	 */
	public function getScriptRequestPathAndFilename() {
		if (isset($this->server['SCRIPT_NAME'])) {
			return $this->server['SCRIPT_NAME'];
		}
		if (isset($this->server['ORIG_SCRIPT_NAME'])) {
			return $this->server['ORIG_SCRIPT_NAME'];
		}
		return '';
	}

	/**
	 * Returns the relative path (ie. relative to the web root) to the script as
	 * it was accessed through the webserver.
	 *
	 * @return string Relative path to the PHP script as accessed through the web
	 * @api
	 */
	public function getScriptRequestPath() {
		$requestPathSegments = explode('/', $this->getScriptRequestPathAndFilename());
		array_pop($requestPathSegments);
		return implode('/', $requestPathSegments) . '/';
	}

	/**
	 * Tries to detect the base URI of request.
	 *
	 * @return void
	 */
	protected function detectBaseUri() {
		if (isset($this->settings['http']['baseUri']) && $this->settings['http']['baseUri'] !== NULL) {
			$this->baseUri = new Uri($this->settings['http']['baseUri']);
		} else {
			$this->baseUri = clone $this->uri;
			$this->baseUri->setQuery(NULL);
			$this->baseUri->setFragment(NULL);
			$this->baseUri->setPath($this->getScriptRequestPath());
		}
	}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @param array $getArguments Arguments as found in $_GET
	 * @param array $postArguments Arguments as found in $_POST
	 * @param array $uploadArguments Arguments as found in $_FILES
	 * @return array the unified arguments
	 */
	protected function buildUnifiedArguments(array $getArguments, array $postArguments, array $uploadArguments) {
		$arguments = $getArguments;
		$contentArguments = NULL;

		if ($this->method === 'POST') {
			$contentArguments = ($postArguments !== array()) ? $postArguments : $this->decodeBodyArguments($this->getContent(), $this->headers->get('Content-Type'));
		} elseif ($this->method === 'PUT') {
			$contentArguments = $this->decodeBodyArguments($this->getContent(), $this->headers->get('Content-Type'));
		}

		if ($contentArguments !== NULL) {
			$arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $contentArguments);
		}
		$arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $this->untangleFilesArray($uploadArguments));
		return $arguments;
	}

	/**
	 * Decodes the given request body, depending on the given content type.
	 *
	 * Currently JSON, XML and encoded forms are supported. The media types accepted
	 * for choosing the respective decoding algorithm are rather broad. This method
	 * does, for example, accept "text/x-json" although "application/json" is the
	 * only valid (that is, IANA registered) media type for JSON.
	 *
	 * In future versions of FLOW3, this part maybe extensible by third-party code.
	 * For the time being, only the mentioned media types are supported.
	 *
	 * Errors are silently ignored and result in an empty array.
	 *
	 * @param string $body The request body
	 * @param string $mediaType The IANA Media Type
	 * @return array The decoded body
	 */
	protected function decodeBodyArguments($body, $mediaType) {
		switch (self::trimMediaType($mediaType)) {
			case 'application/json':
			case 'application/x-javascript':
			case 'text/javascript':
			case 'text/x-javascript':
			case 'text/x-json':
				$arguments = json_decode($body, TRUE);
				if ($arguments === NULL) {
					return array();
				}
			break;
			case 'text/xml':
			case 'application/xml':
				try {
					$xmlElement = new \SimpleXMLElement(urldecode($body), LIBXML_NOERROR);
				} catch (\Exception $e) {
					return array();
				}
				$arguments = Arrays::convertObjectToArray($xmlElement);
			break;
			case 'application/x-www-form-urlencoded':
			default:
				parse_str($body, $arguments);
			break;
		}
		return $arguments;
	}

	/**
	 * Transforms the convoluted _FILES superglobal into a manageable form.
	 *
	 * @param array $convolutedFiles The _FILES superglobal
	 * @return array Untangled files
	 */
	protected function untangleFilesArray(array $convolutedFiles) {
		$untangledFiles = array();

		$fieldPaths = array();
		foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
			if (!is_array($fieldInformation['error'])) {
				$fieldPaths[] = array($firstLevelFieldName);
			} else {
				$newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
				array_walk($newFieldPaths,
					function(&$value, $key) {
						$value = explode('/', $value);
					}
				);
				$fieldPaths = array_merge($fieldPaths, $newFieldPaths);
			}
		}

		foreach ($fieldPaths as $fieldPath) {
			if (count($fieldPath) === 1) {
				$fileInformation = $convolutedFiles[$fieldPath{0}];
			} else {
				$fileInformation = array();
				foreach ($convolutedFiles[$fieldPath{0}] as $key => $subStructure) {
					$fileInformation[$key] = Arrays::getValueByPath($subStructure, array_slice($fieldPath, 1));
				}
			}
			if (isset($fileInformation['error']) && $fileInformation['error'] !== \UPLOAD_ERR_NO_FILE) {
				$untangledFiles = Arrays::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
			}
		}
		return $untangledFiles;
	}

	/**
	 * Returns and array of all possibles "field paths" for the given array.
	 *
	 * @param array $structure The array to walk through
	 * @param string $firstLevelFieldName
	 * @return array An array of paths (as strings) in the format "key1/key2/key3" ...
	 */
	protected function calculateFieldPaths(array $structure, $firstLevelFieldName = NULL) {
		$fieldPaths = array();
		if (is_array($structure)) {
			foreach ($structure as $key => $subStructure) {
				$fieldPath = ($firstLevelFieldName !== NULL ? $firstLevelFieldName . '/' : '') . $key;
				if (is_array($subStructure)) {
					foreach ($this->calculateFieldPaths($subStructure) as $subFieldPath) {
						$fieldPaths[] = $fieldPath . '/' . $subFieldPath;
					}
				} else {
					$fieldPaths[] = $fieldPath;
				}
			}
		}
		return $fieldPaths;
	}

	/**
	 * Parses a RFC 2616 content negotiation header field by evaluating the Quality
	 * Values and splitting the options into an array list, ordered by user preference.
	 *
	 * @param string $rawValues The raw Accept* Header field value
	 * @return array The parsed list of field values, ordered by user preference
	 */
	public static function parseContentNegotiationQualityValues($rawValues) {
		$acceptedTypes = array_map(
			function($acceptType) {
					$typeAndQuality = preg_split('/;\s*q=/', $acceptType);
					return array($typeAndQuality[0], (isset($typeAndQuality[1]) ? (float)$typeAndQuality[1] : ''));
			}, preg_split('/,\s*/', $rawValues)
		);

		$flattenedAcceptedTypes = array();
		$valuesWithoutQualityValue = array(array(), array(), array(), array());
		foreach ($acceptedTypes as $typeAndQuality) {
			if ($typeAndQuality[1] === '') {
				$parsedType = Request::parseMediaType($typeAndQuality[0]);
				if ($parsedType['type'] === '*') {
					$valuesWithoutQualityValue[3][$typeAndQuality[0]] = TRUE;
				} elseif ($parsedType['subtype'] === '*') {
					$valuesWithoutQualityValue[2][$typeAndQuality[0]] = TRUE;
				} elseif ($parsedType['parameters'] === array()) {
					$valuesWithoutQualityValue[1][$typeAndQuality[0]] = TRUE;
				} else {
					$valuesWithoutQualityValue[0][$typeAndQuality[0]] = TRUE;
				}
			} else {
				$flattenedAcceptedTypes[$typeAndQuality[0]] = $typeAndQuality[1];
			}
		}
		$valuesWithoutQualityValue = array_merge(array_keys($valuesWithoutQualityValue[0]), array_keys($valuesWithoutQualityValue[1]), array_keys($valuesWithoutQualityValue[2]), array_keys($valuesWithoutQualityValue[3]));
		arsort($flattenedAcceptedTypes);
		$parsedValues = array_merge($valuesWithoutQualityValue, array_keys($flattenedAcceptedTypes));
		return $parsedValues;
	}

	/**
	 * Parses a RFC 2616 Media Type and returns its parts in an associative array.
	 *
	 * media-type = type "/" subtype *( ";" parameter)
	 *
	 * The media type "text/html; charset=UTF-8" would be parsed and returned with
	 * the following array keys and values:
	 *
	 * "type" => the type as a string, "text"
	 * "subtype" => the subtype as a string, "html"
	 * "parameters" => an array of parameter names and values, array("charset" => "UTF-8")
	 *
	 * @param string $rawMediaType The raw media type, for example "application/json; charset=UTF-8"
	 * @return array An associative array with parsed information
	 */
	public static function parseMediaType($rawMediaType) {
		preg_match(self::PATTERN_SPLITMEDIATYPE, $rawMediaType, $matches);
		$result = array();
		$result['type'] = isset($matches['type']) ? $matches['type'] : '';
		$result['subtype'] = isset($matches['subtype']) ? $matches['subtype'] : '';
		$result['parameters'] = array();

		if (isset($matches['parameters'])) {
			foreach (Arrays::trimExplode(';', $matches['parameters']) as $parameter) {
				$pieces = explode('=', $parameter);
				if (count($pieces) === 2) {
					$name = trim($pieces[0]);
					$result['parameters'][$name] = trim($pieces[1]);
				}
			}
		}
		return $result;
	}

	/**
	 * Checks if the given media range and the media type match.
	 *
	 * The pattern used by this function splits each into media type and subtype
	 * and ignores possible additional parameters or extensions. The prepared types
	 * and subtypes are then compared and wildcards are considered in this process.
	 *
	 * Media ranges are explained in RFC 2616, section 14.1. "Accept".
	 *
	 * @param string $mediaRange The media range, for example "text/*"
	 * @param string $mediaType The media type to match against, for example "text/html"
	 * @return boolean TRUE if both match, FALSE if they don't match or either of them is invalid
	 */
	public static function mediaRangeMatches($mediaRange, $mediaType) {
		preg_match(self::PATTERN_SPLITMEDIARANGE, $mediaRange, $mediaRangeMatches);
		preg_match(self::PATTERN_SPLITMEDIATYPE, $mediaType, $mediaTypeMatches);
		if ($mediaRangeMatches === array() || $mediaTypeMatches === array()) {
			return FALSE;
		}

		$typeMatches = ($mediaRangeMatches['type'] === '*' || $mediaRangeMatches['type'] === $mediaTypeMatches['type']);
		$subtypeMatches = ($mediaRangeMatches['subtype'] === '*' || $mediaRangeMatches['subtype'] === $mediaTypeMatches['subtype']);

		return ($typeMatches && $subtypeMatches);
	}

	/**
	 * Strips off any parameters from the given media type and returns just the type
	 * and subtype in the format "type/subtype".
	 *
	 * @param string $rawMediaType The full media type, for example "application/json; charset=UTF-8"
	 * @return string Just the type and subtype, for example "application/json"
	 */
	static public function trimMediaType($rawMediaType) {
		$pieces = self::parseMediaType($rawMediaType);
		return trim(sprintf('%s/%s', $pieces['type'], $pieces['subtype']), '/') ?: NULL;
	}
}

?>
