<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

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

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/MethodsTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Method Name Filter
 *
 */
class PointcutMethodNameFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesIgnoresFinalMethodsEvenIfTheirNameMatches() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				final public function someFinalMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'));

		$methodNameFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter('someFinalMethod');
		$methodNameFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($methodNameFilter->matches($className, 'someFinalMethod', $className, 1));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTakesTheVisibilityModifierIntoAccountIfOneWasSpecified() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				public function somePublicMethod() {}
				protected function someProtectedMethod() {}
				private function somePrivateMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'));

		$methodNameFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter('some.*', 'public');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', NULL, 1));

		$methodNameFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter('some.*', 'protected');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', NULL, 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesChecksTheAvailablityOfAnArgumentNameIfArgumentConstraintsHaveBeenConfigured() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				public function somePublicMethod(\$arg1) {}
				public function someOtherPublicMethod(\$arg1, \$arg2 = 'default') {}
				public function someThirdMethod(\$arg1, \$arg2, \$arg3 = 'default') {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'));

		$mockSystemLogger = $this->getMock('TYPO3\FLOW3\Log\Logger');
		$mockSystemLogger->expects($this->once())->method('log')->with($this->equalTo(
			'The argument "arg2" declared in pointcut does not exist in method ' . $className . '->somePublicMethod'
		));

		$argumentConstraints = array(
			'arg1' => array(
				'operator' => '==',
				'value' => 'someValue'
			),
			'arg2.some.sub.object' => array(
				'operator' => '==',
				'value' => 'someValue'
			)
		);

		$methodNameFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$methodNameFilter->injectSystemLogger($mockSystemLogger);

		$methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1);

		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someOtherPublicMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someThirdMethod', $className, 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsReturnsTheMethodArgumentConstraintsDefinitions() {
		$argumentConstraints = array(
			'arg2' => array(
				'operator' => '==',
				'value' => 'someValue'
			)
		);

		$expectedRuntimeEvaluations = array(
			'methodArgumentConstraints' => $argumentConstraints
		);

		$methodNameFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);

		$this->assertEquals($expectedRuntimeEvaluations, $methodNameFilter->getRuntimeEvaluationsDefinition(), 'The argument constraint definitions have not been returned as expected.');
	}
}

?>