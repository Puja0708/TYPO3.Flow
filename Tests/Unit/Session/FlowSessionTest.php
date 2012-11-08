<?php
namespace TYPO3\Flow\Tests\Unit\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Session\FlowSession;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cache\Backend\TransientMemoryBackend;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Cookie;

/**
 * Unit tests for the Flow Session implementation
 */
class FlowSessionTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $httpRequest;

	/**
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $httpResponse;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $mockBootstrap;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var array
	 */
	protected $settings = array(
		'session' => array(
			'inactivityTimeout' => 3600,
			'FlowSession' => array(
				'name' => 'TYPO3_Flow_Session',
				'garbageCollectionProbability' => 1,
				'cookie' => array(
					'lifetime' => 0,
					'path' => '/',
					'secure' => FALSE,
					'httponly' => TRUE,
					'domain' => NULL
				)
			)
		)
	);

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setup();

		$this->httpRequest = Request::create(new Uri('http://localhost'));
		$this->httpResponse = new Response();

		$mockRequestHandler = $this->getMock('TYPO3\Flow\Http\RequestHandler', array(), array(), '', FALSE, FALSE);
		$mockRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->httpRequest));
		$mockRequestHandler->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->httpResponse));

		$this->mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE, FALSE);
		$this->mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE, FALSE);
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfNoSessionCookieExists() {
		$session = new FlowSession();
		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfNoLastActivityCookieExists() {
		$session = new FlowSession();
		$this->inject($session, 'sessionCookie', new Cookie('TYPO3_Flow_Session', '12345abcdef'));
		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->initializeObject();

		$session->start();
		$session->close();

		$this->assertTrue($session->canBeResumed());

		$this->inject($session, 'sessionLastActivityCookie', new Cookie('TYPO3_Flow_Session_LastActivity', (time() - 4000)));
		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function isStartedReturnsFalseByDefault() {
		$session = new FlowSession();
		$this->assertFalse($session->isStarted());
	}

	/**
	 * @test
	 */
	public function isStartedReturnsTrueAfterSessionHasBeenStarted() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$session->initializeObject();
		$session->start();
		$this->assertTrue($session->isStarted());
	}

	/**
	 * @test
	 */
	public function resumeUpdatesTheLastActivityTimestampAndSetsCookiesInTheResponse() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

			// initializeObject sets the cookie in the HTTP Request
		$session->initializeObject();
		$session->start();

			// close does not remove the cookie again, so, if we don't call initializeObject
			// again, the session cookie and last activity cookie is still stored in the
			// session object and is available for resume()
		$session->close();

		$lastActivityCookie = ($this->httpResponse->getCookie('TYPO3_Flow_Session_LastActivity'));
		$toBeReplacedActivityTimestamp = ($lastActivityCookie->getValue() - 100);
		$lastActivityCookie->setValue($toBeReplacedActivityTimestamp);

		$lastActivitySecondsAgo = $session->resume();

		$this->assertGreaterThan($toBeReplacedActivityTimestamp, $lastActivityCookie->getValue());
		$this->assertEquals(($lastActivityCookie->getValue() - $toBeReplacedActivityTimestamp) , $lastActivitySecondsAgo);
	}

	/**
	 * @test
	 */
	public function startPutsACookieIntoTheHttpResponse() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$session->initializeObject();

		$session->start();

		$cookie = $this->httpResponse->getCookie('TYPO3_Flow_Session');
		$this->assertNotNull($cookie);
		$this->assertEquals($session->getId(), $cookie->getValue());
	}

	/**
	 * @test
	 */
	public function getIdReturnsTheCurrentSessionIdentifier() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->initializeObject();

		try {
			$session->getId();
			$this->fail('No exception thrown although the session was not started yet.');
		} catch (\TYPO3\Flow\Session\Exception\SessionNotStartedException $e) {
			$session->start();
			$this->assertEquals(32, strlen($session->getId()));
		}
	}

	/**
	 * @test
	 */
	public function renewIdSetsANewSessionIdentifier() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->initializeObject();
		$session->start();

		$oldSessionId = $session->getId();
		$session->renewId();
		$newSessionId = $session->getId();
		$this->assertNotEquals($oldSessionId, $newSessionId);
	}

	/**
	 * @test
	 */
	public function renewIdTransfersSessionDataFromOldIdToNewIdAndFlushesOldData() {
		$session = new FlowSession();
		$cache = $this->createCache();

		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);
		$session->initializeObject();
		$session->start();
		$oldSessionIdentifier = $session->getId();

		$session->putData('some key', 'some value');
		$this->assertTrue($cache->has($oldSessionIdentifier . md5('some key')));

		$session->renewId();

		$this->assertEquals('some value', $session->getData('some key'));
		$this->assertFalse($cache->has($oldSessionIdentifier . md5('some key')), 'old session data not removed');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getDataThrowsExceptionIfSessionIsNotStarted() {
		$session = new FlowSession();
		$session->getData('some key');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function putDataThrowsExceptionIfSessionIsNotStarted() {
		$session = new FlowSession();
		$session->putData('some key', 'some value');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\DataNotSerializableException
	 */
	public function putDataThrowsExceptionIfTryingToPersistAResource() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$session->initializeObject();
		$session->start();
		$resource = fopen(__FILE__, 'r');
		$session->putData('some key', $resource);
	}

	/**
	 * @test
	 */
	public function getDataReturnsDataPreviouslySetWithPutData() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->initializeObject();

		$session->start();

		$this->assertFalse($session->hasKey('some key'));
		$session->putData('some key', 'some value');
		$this->assertEquals('some value', $session->getData('some key'));
		$this->assertTrue($session->hasKey('some key'));
	}

	/**
	 * @test
	 */
	public function twoSessionsDontConflictIfUsingSameEntryIdentifiers() {
		$cache = $this->createCache();

		$session1 = new FlowSession();
		$this->inject($session1, 'bootstrap', $this->mockBootstrap);
		$this->inject($session1, 'settings', $this->settings);
		$this->inject($session1, 'cache', $cache);
		$session1->initializeObject();
		$session1->start();

		$session2 = new FlowSession();
		$this->inject($session2, 'bootstrap', $this->mockBootstrap);
		$this->inject($session2, 'settings', $this->settings);
		$this->inject($session2, 'cache', $cache);
		$session2->initializeObject();
		$session2->start();

		$session1->putData('foo', 'bar');
		$session2->putData('foo', 'baz');

		$this->assertEquals('bar', $session1->getData('foo'));
		$this->assertEquals('baz', $session2->getData('foo'));
	}

	/**
	 * @test
	 */
	public function closeFlagsTheSessionAsClosed() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->initializeObject();

		$session->start();
		$this->assertTrue($session->isStarted());

		$session->close();
		$this->assertFalse($session->isStarted());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function destroyThrowsExceptionIfSessionIsNotStarted() {
		$session = new FlowSession();
		$session->destroy();
	}

	/**
	 * @test
	 */
	public function destroyRemovesAllSessionDataFromTheCurrentSessionButNotFromOtherSessions() {
		$session1 = new FlowSession();
		$session2 = new FlowSession();

		$this->inject($session1, 'bootstrap', $this->mockBootstrap);
		$this->inject($session2, 'bootstrap', $this->mockBootstrap);
		$this->inject($session1, 'settings', $this->settings);
		$this->inject($session2, 'settings', $this->settings);

		$cache = $this->createCache();
		$this->inject($session1, 'cache', $cache);
		$this->inject($session2, 'cache', $cache);

		$session1->initializeObject();
		$session2->initializeObject();

		$session1->start();
		$session2->start();

		$session1->putData('session 1 key 1', 'some value');
		$session1->putData('session 1 key 2', 'some other value');
		$session2->putData('session 2 key', 'some value');

		$session1->destroy(__METHOD__);

		$this->assertFalse($session1->hasKey('session 1 key 1'));
		$this->assertFalse($session1->hasKey('session 1 key 2'));
		$this->assertTrue($session2->hasKey('session 2 key'), 'Entry in session was also removed.');
	}

	/**
	 * @test
	 */
	public function autoExpireRemovesAllSessionDataOfTheExpiredSession() {
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);
		$session->initializeObject();

		$session->start();
		$sessionIdentifier = $session->getId();

		$session->putData('session 1 key 1', 'some value');
		$session->putData('session 1 key 2', 'some other value');

		$this->assertTrue($cache->has($sessionIdentifier . md5('session 1 key 1')));
		$this->assertTrue($cache->has($sessionIdentifier . md5('session 1 key 2')));

		$session->close();

		$this->inject($session, 'sessionLastActivityCookie', new Cookie('TYPO3_Flow_Session_LastActivity', (time() - 4000)));

			// canBeResumed implicitly calls autoExpire():
		$this->assertFalse($session->canBeResumed());

		$this->assertFalse($cache->has($sessionIdentifier . md5('session 1 key 1')));
		$this->assertFalse($cache->has($sessionIdentifier . md5('session 1 key 2')));
	}

	/**
	 * @test
	 */
	public function shutdownObjectTriggersGarbageCollectionForExpiredSessions() {
		$settings = $this->settings;
		$settings['session']['inactivityTimeout'] = 5000;
		$settings['session']['FlowSession']['garbageCollectionProbability'] = 100;

		$cache = $this->createCache();

			// Create a session which first runs fine and then expires by later modifying
			// the inactivity timeout:
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'cache', $cache);
		$session->injectSettings($settings);

		$session->initializeObject();
		$session->start();
		$sessionIdentifier1 = $session->getId();
		$session->putData('session 1 key 1', 'session 1 value 1');
		$session->putData('session 1 key 2', 'session 1 value 2');
		$session->close();

		$session->resume();
		$this->assertTrue($session->isStarted());
		$this->inject($session, 'sessionLastActivityCookie', new Cookie('TYPO3_Flow_Session_LastActivity', (time() - 4000)));
		$session->close();

			// Now the previously valid session expires:
		$settings['session']['inactivityTimeout'] = 3000;

			// Create a second session which should remove the first expired session
			// implicitly by calling autoExpire()
		$session = new FlowSession();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'cache', $cache);
		$session->injectSettings($settings);

		$session->initializeObject();
		$session->start();
		$sessionIdentifier2 = $session->getId();
		$session->putData('session 2 key 1', 'session 1 value 1');
		$session->putData('session 2 key 2', 'session 1 value 2');
		$session->shutdownObject();

			// Check how the cache looks like - data of session 1 should be gone:
		$this->assertFalse($cache->has($sessionIdentifier1 . md5('session 1 key 1')), 'session 1 key 1 still there');
		$this->assertFalse($cache->has($sessionIdentifier1 . md5('session 1 key 2')), 'session 1 key 2 still there');
		$this->assertTrue($cache->has($sessionIdentifier2 . md5('session 2 key 1')), 'session 2 key 1 not there');
		$this->assertTrue($cache->has($sessionIdentifier2 . md5('session 2 key 2')), 'session 2 key 2 not there');
	}

	/**
	 * Creates a cache for testing
	 */
	protected function createCache() {
		$backend = new TransientMemoryBackend(new ApplicationContext('Testing'), array());
		$cache = new VariableFrontend('SessionTest', $backend);
		return $cache;
	}
}