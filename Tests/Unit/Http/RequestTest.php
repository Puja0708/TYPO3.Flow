<?php
namespace TYPO3\FLOW3\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Uri;

/**
 * Testcase for the Http Request class
 *
 */
class RequestTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createFromEnvironmentCreatesAReasonableRequestObjectFromTheSuperGlobals() {
		$_GET = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
		$_POST = array();
		$_COOKIE = array();
		$_FILES = array();
		$_SERVER = array (
			'REDIRECT_FLOW3_CONTEXT' => 'Development',
			'REDIRECT_FLOW3_REWRITEURLS' => '1',
			'REDIRECT_STATUS' => '200',
			'FLOW3_CONTEXT' => 'Development',
			'FLOW3_REWRITEURLS' => '1',
			'HTTP_HOST' => 'dev.blog.rob',
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
			'HTTP_CONNECTION' => 'keep-alive',
			'PATH' => '/usr/bin:/bin:/usr/sbin:/sbin',
			'SERVER_SIGNATURE' => '',
			'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.3.8',
			'SERVER_NAME' => 'dev.blog.rob',
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => '80',
			'REMOTE_ADDR' => '127.0.0.1',
			'DOCUMENT_ROOT' => '/opt/local/apache2/htdocs/Development/FLOW3/Applications/Blog/Web/',
			'SERVER_ADMIN' => 'rl@robertlemke.de',
			'SCRIPT_FILENAME' => '/opt/local/apache2/htdocs/Development/FLOW3/Applications/Blog/Web/index.php',
			'REMOTE_PORT' => '51439',
			'REDIRECT_QUERY_STRING' => 'getKey1=getValue1&getKey2=getValue2',
			'REDIRECT_URL' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => 'foo=bar',
			'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
			'REQUEST_TIME' => 1326472534,
		);

		$request = Request::createFromEnvironment();

		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals('http://dev.blog.rob/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2', (string)$request->getUri());
	}

	/**
	 * @test
	 */
	public function constructRecognizesSslSessionIdAsIndicatorForSsl() {
		$get = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
		$post = array();
		$cookie = array();
		$files = array();
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'SERVER_NAME' => 'dev.blog.rob',
			'SERVER_ADDR' => '127.0.0.1',
			'REMOTE_ADDR' => '127.0.0.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => 'foo=bar',
			'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
			'SSL_SESSION_ID' => '12345'
		);

		$request = new Request($get, $post, $cookie, $files, $server);
		$this->assertEquals('https', $request->getUri()->getScheme());
		$this->assertTrue($request->isSecure());
	}

	/**
	 * @test
	 */
	public function createUsesReasonableDefaultsForCreatingANewRequest() {
		$uri = new Uri('http://flow3.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri);

		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals($uri, $request->getUri());

		$uri = new Uri('https://flow3.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri);

		$this->assertEquals($uri, $request->getUri());

		$uri = new Uri('http://flow3.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri, 'POST');

		$this->assertEquals('POST', $request->getMethod());
		$this->assertEquals($uri, $request->getUri());
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @test
	 */
	public function createRejectsInvalidMethods() {
		$uri = new Uri('http://flow3.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		Request::create($uri, 'STEAL');
	}

	/**
	 * HTML 2.0 and up
	 * (see also HTML5, section 4.10.22.5 "URL-encoded form data")
	 *
	 * @test
	 */
	public function createSetsTheContentTypeHeaderToFormUrlEncodedByDefaultIfRequestMethodSuggestsIt() {
		$uri = new Uri('http://flow3.typo3.org/foo');
		$request = Request::create($uri, 'POST');

		$this->assertEquals('application/x-www-form-urlencoded', $request->getHeaders()->get('Content-Type'));
	}

	/**
	 * @test
	 */
	public function createSubRequestCreatesAnMvcRequestConnectedToTheParentRequest() {
		$uri = new Uri('http://flow3.typo3.org');
		$request = Request::create($uri);

		$subRequest = $request->createActionRequest();
		$this->assertInstanceOf('TYPO3\FLOW3\Mvc\ActionRequest', $subRequest);
		$this->assertSame($request, $subRequest->getParentRequest());
	}

	/**
	 * @test
	 */
	public function createSubRequestMapsTheArgumentsOfTheHttpRequestToTheNewActionRequest() {
		$uri = new Uri('http://flow3.typo3.org/page.html?foo=bar&__baz=quux');
		$request = Request::create($uri);

		$subRequest = $request->createActionRequest();
		$this->assertEquals('bar', $subRequest->getArgument('foo'));
		$this->assertEquals('quux', $subRequest->getInternalArgument('__baz'));
	}

	/**
	 * @return array
	 */
	public function invalidMethods() {
		return array(
			array('get'),
			array('mett'),
			array('post'),
		);
	}

	/**
	 * RFC 2616 / 5.1.1
	 *
	 * @test
	 * @dataProvider invalidMethods
	 * @expectedException InvalidArgumentException
	 */
	public function setMethodDoesNotAcceptInvalidRequestMethods($invalidMethod) {
		$request = Request::create(new Uri('http://flow3.typo3.org'));
		$request->setMethod($invalidMethod);
	}

	/**
	 * @return array
	 */
	public function validMethods() {
		return array(
			array('GET'),
			array('HEAD'),
			array('POST'),
		);
	}

	/**
	 * RFC 2616 / 5.1.1
	 *
	 * @test
	 * @dataProvider validMethods
	 */
	public function setMethodAcceptsValidRequestMethods($validMethod) {
		$request = Request::create(new Uri('http://flow3.typo3.org'));
		$request->setMethod($validMethod);
		$this->assertSame($validMethod, $request->getMethod());
	}

	/**
	 * RFC 2616 / 5.1.2
	 *
	 * @test
	 */
	public function getReturnsTheRequestUri() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$uri = new Uri('http://dev.blog.rob/foo/bar');
		$request = new Request(array(), array(), array(), array(), $server);
		$this->assertEquals($uri, $request->getUri());
	}

	/**
	 * @test
	 */
	public function getBaseUriReturnsTheDetectedBaseUri() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = new Request(array(), array(), array(), array(), $server);
		$this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'ORIG_SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = new Request(array(), array(), array(), array(), $server);
		$this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'PHP_SELF' => '/index.php',
		);

		$request = new Request(array(), array(), array(), array(), $server);
		$this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());
	}

	/**
	 * Data Provider
	 */
	public function variousArguments() {
		return array(
			array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array(), array(), array('baz' => 'quux', 'coffee' => 'due')),
			array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('ignore' => 'me'), array(), array('baz' => 'quux', 'coffee' => 'due')),
			array('POST', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('dontignore' => 'me'), array(), array('baz' => 'quux', 'coffee' => 'due', 'dontignore' => 'me')),
			array('PUT', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('dontignore' => 'me'), array(), array('baz' => 'quux', 'coffee' => 'due', 'dontignore' => 'me')),
		);
	}

	/**
	 * @test
	 * @dataProvider variousArguments
	 */
	public function getArgumentsReturnsGetQueryArguments($method, $uriString, $postArguments, $filesArguments, $expectedArguments) {
		$request = Request::create(new Uri($uriString), $method, $postArguments, array(), $filesArguments);
		$this->assertEquals($expectedArguments, $request->getArguments());
	}

	/**
	 * @test
	 */
	public function isSecureReturnsTrueEvenIfTheSchemeIsHttpButTheRequestWasForwardedAndOriginallyWasHttps() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'HTTP_X_FORWARDED_PROTO' => 'https',
			'SERVER_NAME' => 'dev.blog.rob',
			'SERVER_ADDR' => '127.0.0.1',
			'REMOTE_ADDR' => '127.0.0.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => 'foo=bar',
			'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), array(), $server);
		$this->assertEquals('http', $request->getUri()->getScheme());
		$this->assertTrue($request->isSecure());
	}

	/**
	 * RFC 2616 / 9.1.1
	 *
	 * @test
	 */
	public function isMethodSafeReturnsTrueIfTheRequestMethodIsGetOrHead() {
		$request = Request::create(new Uri('http://acme.com'), 'GET');
		$this->assertTrue($request->isMethodSafe());

		$request = Request::create(new Uri('http://acme.com'), 'HEAD');
		$this->assertTrue($request->isMethodSafe());

		$request = Request::create(new Uri('http://acme.com'), 'POST');
		$this->assertFalse($request->isMethodSafe());

		$request = Request::create(new Uri('http://acme.com'), 'PUT');
		$this->assertFalse($request->isMethodSafe());

		$request = Request::create(new Uri('http://acme.com'), 'DELETE');
		$this->assertFalse($request->isMethodSafe());
	}
}

?>