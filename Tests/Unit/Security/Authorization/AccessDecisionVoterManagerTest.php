<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization;

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
 * Testcase for the access decision voter manager
 *
 */
class AccessDecisionVoterManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}
}
?>