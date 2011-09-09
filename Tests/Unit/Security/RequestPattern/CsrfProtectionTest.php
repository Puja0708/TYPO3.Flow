<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the CsrfProtection request pattern
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CsrfProtectionTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequestReturnsFalseIfTheTargetActionIsTaggedWithSkipCsrfProtection() {
		$controllerObjectName = 'SomeControllerObjectName';
		$controllerActionName = 'list';

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipCsrfProtection')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, 'listAction')->will($this->returnValue(TRUE));

		$mockCsrfProtectionPattern = $this->getAccessibleMock('TYPO3\FLOW3\Security\RequestPattern\CsrfProtection', array('dummy'));
		$mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
		$mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
		$mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);

		$this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockRequest));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequestReturnsFalseIfTheTargetActionIsNotMentionedInThePolicy() {
		$controllerObjectName = 'SomeControllerObjectName';
		$controllerActionName = 'list';

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(FALSE));

		$mockCsrfProtectionPattern = $this->getAccessibleMock('TYPO3\FLOW3\Security\RequestPattern\CsrfProtection', array('dummy'));
		$mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
		$mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);

		$this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockRequest));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButNoCsrfTokenHasBeenSent() {
		$controllerObjectName = 'SomeControllerObjectName';
		$controllerActionName = 'list';

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
		$mockRequest->expects($this->once())->method('getInternalArguments')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipCsrfProtection')->will($this->returnValue(FALSE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(TRUE));

		$mockCsrfProtectionPattern = $this->getAccessibleMock('TYPO3\FLOW3\Security\RequestPattern\CsrfProtection', array('dummy'));
		$mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
		$mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
		$mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);

		$this->assertTrue($mockCsrfProtectionPattern->matchRequest($mockRequest));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButTheCsrfTokenIsInvalid() {
		$controllerObjectName = 'SomeControllerObjectName';
		$controllerActionName = 'list';

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
		$mockRequest->expects($this->once())->method('getInternalArguments')->will($this->returnValue(array('__csrfToken' => 'invalidCsrfToken')));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipCsrfProtection')->will($this->returnValue(FALSE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(TRUE));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
		$mockSecurityContext->expects($this->once())->method('isCsrfProtectionTokenValid')->with('invalidCsrfToken')->will($this->returnValue(FALSE));

		$mockCsrfProtectionPattern = $this->getAccessibleMock('TYPO3\FLOW3\Security\RequestPattern\CsrfProtection', array('dummy'));
		$mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
		$mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
		$mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
		$mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

		$this->assertTrue($mockCsrfProtectionPattern->matchRequest($mockRequest));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequestReturnsFalseIfTheTargetActionIsMentionedInThePolicyAndTheCsrfTokenIsValid() {
		$controllerObjectName = 'SomeControllerObjectName';
		$controllerActionName = 'list';

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
		$mockRequest->expects($this->once())->method('getInternalArguments')->will($this->returnValue(array('__csrfToken' => 'validToken')));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipCsrfProtection')->will($this->returnValue(FALSE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(TRUE));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
		$mockSecurityContext->expects($this->once())->method('isCsrfProtectionTokenValid')->with('validToken')->will($this->returnValue(TRUE));

		$mockCsrfProtectionPattern = $this->getAccessibleMock('TYPO3\FLOW3\Security\RequestPattern\CsrfProtection', array('dummy'));
		$mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
		$mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
		$mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
		$mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

		$this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockRequest));
	}
}
?>