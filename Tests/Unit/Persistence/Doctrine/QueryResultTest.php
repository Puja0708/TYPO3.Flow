<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Doctrine;

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
 * Testcase for \TYPO3\FLOW3\Persistence\QueryResult
 *
 */
class QueryResultTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\QueryResult
	 */
	protected $queryResult;

	/**
	 * @var \TYPO3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->query = $this->getMockBuilder('TYPO3\FLOW3\Persistence\Doctrine\Query')->disableOriginalConstructor()->disableOriginalClone()->getMock();
		$this->queryResult = new \TYPO3\FLOW3\Persistence\Doctrine\QueryResult($this->query);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertInstanceOf('TYPO3\FLOW3\Persistence\QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResult->getQuery());
	}
}
?>