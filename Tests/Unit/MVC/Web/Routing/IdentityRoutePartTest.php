<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web\Routing;

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
 * Testcase for the MVC Web Routing IdentityRoutePart Class
 *
 */
class IdentityRoutePartTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\IdentityRoutePart
	 */
	protected $identityRoutePart;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMappingRepository
	 */
	protected $mockObjectPathMappingRepository;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->identityRoutePart = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\IdentityRoutePart', array('createPathSegmentForObject'));

		$this->mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$this->identityRoutePart->_set('reflectionService', $this->mockReflectionService);

		$this->mockObjectPathMappingRepository = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMappingRepository');
		$this->identityRoutePart->_set('objectPathMappingRepository', $this->mockObjectPathMappingRepository);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUriPatternReturnsTheSpecifiedUriPatternIfItsNotEmpty() {
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertSame('SomeUriPattern', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUriPatternReturnsAnEmptyStringIfObjectTypeHasNotIdentityPropertiesAndNoPatternWasSpecified() {
		$mockClassSchema = $this->getMock('TYPO3\FLOW3\Reflection\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array()));

		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('SomeObjectType')->will($this->returnValue($mockClassSchema));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertSame('', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUriPatternReturnsBasedOnTheIdentityPropertiesOfTheObjectTypeIfNoPatternWasSpecified() {
		$mockClassSchema = $this->getMock('TYPO3\FLOW3\Reflection\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array('property1' => 'string', 'property2' => 'integer', 'property3' => 'DateTime')));

		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('SomeObjectType')->will($this->returnValue($mockClassSchema));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertSame('{property1}/{property2}/{property3}', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchValueReturnsFalseIfTheGivenValueIsEmptyOrNull() {
		$this->assertFalse($this->identityRoutePart->_call('matchValue', ''));
		$this->assertFalse($this->identityRoutePart->_call('matchValue', NULL));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchValueReturnsFalseIfNoObjectPathMappingCouldBeFound() {
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath')->will($this->returnValue(NULL));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertFalse($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchValueSetsTheIdentifierOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound() {
		$mockObjectPathMapping = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getIdentifier')->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath')->will($this->returnValue($mockObjectPathMapping));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');

		$this->assertTrue($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
		$this->assertSame('TheIdentifier', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findValueToMatchReturnsAnEmptyStringIfTheRoutePathIsEmpty() {
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', NULL));
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', ''));
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', '/'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findValueToMatchReturnsTheRoutePathIfNoSplitStringIsSpecified() {
		$this->assertSame('The/Complete/RoutPath', $this->identityRoutePart->_call('findValueToMatch', 'The/Complete/RoutPath'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findValueToMatchReturnsTheRoutePathIfTheSpecifiedSplitStringCantBeFoundInTheRoutePath() {
		$this->identityRoutePart->setUriPattern('');
		$this->identityRoutePart->setSplitString('SplitStringThatIsNotInTheCurrentRoutePath');
		$this->assertSame('The/Complete/RoutPath', $this->identityRoutePart->_call('findValueToMatch', 'The/Complete/RoutPath'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findValueToMatchReturnsTheSubstringOfTheRoutePathThatComesBeforeTheSpecifiedSplitStringIfTheUriPatternIsEmpty() {
		$this->identityRoutePart->setUriPattern('');
		$this->identityRoutePart->setSplitString('TheSplitString');
		$this->assertSame('First/Part/Of/The/Complete/RoutPath/', $this->identityRoutePart->_call('findValueToMatch', 'First/Part/Of/The/Complete/RoutPath/TheSplitString/SomeThingElse'));
	}

	/**
	 * data provider for findValueToMatchTests()
	 * @return array
	 */
	public function findValueToMatchProvider() {
		return array(
			array('staticPattern/Foo', 'staticPattern', 'Foo', 'staticPattern'),
			array('staticPattern/Foo', 'staticPattern', 'NonExistingSplitString', ''),
			array('The/Route/Path', '{property1}/{property2}', 'Path', 'The/Route'),
			array('static/dynamic/splitString', 'static/{property1}', 'splitString', 'static/dynamic'),
			array('dynamic/exceeding/splitString', '{property1}', 'splitString', ''),
			array('dynamic1static1dynamic2/static2splitString', '{property1}static1{property2}/static2', 'splitString', 'dynamic1static1dynamic2/static2'),
			array('static1dynamic1dynamic2/static2splitString', 'static1{property1}{property2}/static2', 'splitString', 'static1dynamic1dynamic2/static2'),
		);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @dataProvider findValueToMatchProvider
	 * @param string $routePath
	 * @param string $uriPattern
	 * @param string $splitString
	 * @param string $expectedResult
	 * @return void
	 */
	public function findValueToMatchTests($routePath, $uriPattern, $splitString, $expectedResult) {
		$this->identityRoutePart->setUriPattern($uriPattern);
		$this->identityRoutePart->setSplitString($splitString);
		$this->assertSame($expectedResult, $this->identityRoutePart->_call('findValueToMatch', $routePath));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueReturnsFalseIfTheGivenValueIsNotOfTheSpecifiedType() {
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertFalse($this->identityRoutePart->_call('resolveValue', new \stdClass()));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueSetsTheValueToThePathSegmentOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound() {
		$object = new \stdClass();
		$mockObjectPathMapping = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getPathSegment')->will($this->returnValue('ThePathSegment'));
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue($mockObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('ThePathSegment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueCreatesAndStoresANewObjectPathMappingIfNoMatchingObjectPathMappingWasFound() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment')->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('The/Path/Segment');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('The/Path/Segment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueAppendsCounterIfNoMatchingObjectPathMappingWasFoundAndCreatedPathSegmentIsNotUnique() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$existingObjectPathMapping = new \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping();
		$existingObjectPathMapping->setObjectType('stdClass');
		$existingObjectPathMapping->setUriPattern('SomeUriPattern');
		$existingObjectPathMapping->setPathSegment('The/Path/Segment');
		$existingObjectPathMapping->setIdentifier('AnotherIdentifier');

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->at(1))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment')->will($this->returnValue($existingObjectPathMapping));
		$this->mockObjectPathMappingRepository->expects($this->at(2))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment-1')->will($this->returnValue($existingObjectPathMapping));
		$this->mockObjectPathMappingRepository->expects($this->at(3))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment-2')->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('The/Path/Segment-2');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('The/Path/Segment-2', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\InfiniteLoopException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueThrowsInfiniteLoopExceptionIfNoUniquePathSegmentCantBeFound() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$existingObjectPathMapping = new \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping();
		$existingObjectPathMapping->setObjectType('stdClass');
		$existingObjectPathMapping->setUriPattern('SomeUriPattern');
		$existingObjectPathMapping->setPathSegment('The/Path/Segment');
		$existingObjectPathMapping->setIdentifier('AnotherIdentifier');

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->atLeastOnce())->method('findOneByObjectTypeUriPatternAndPathSegment')->will($this->returnValue($existingObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->_call('resolveValue', $object);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function createPathSegmentForObjectReturnsTheCleanedUpObjectIdentifierIfUriPatternIsEmpty() {
		$identityRoutePart = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\IdentityRoutePart', array('dummy'));
		$identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);
		$identityRoutePart->setUriPattern('');

		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('Objäct--Identifüer/---'));

		$actualResult = $identityRoutePart->_call('createPathSegmentForObject', $object);
		$this->assertSame('Objaect-Identifueer', $actualResult);
	}

	/**
	 * data provider for createPathSegmentForObjectTests()
	 * @return array
	 */
	public function createPathSegmentForObjectProvider() {
		$object = new \stdClass();
		$object->property1 = 'Property1Value';
		$object->property2 = 'Property2Välüe';
		$object->dateProperty = new \DateTime('1980-12-13');
		$subObject = new \stdClass();
		$subObject->subObjectProperty = 'SubObjectPropertyValue';
		$object->subObject = $subObject;
		return array(
			array($object, '{property1}', 'Property1Value'),
			array($object, '{property2}', 'Property2Vaeluee'),
			array($object, '{property1}{property2}', 'Property1ValueProperty2Vaeluee'),
			array($object, '{property1}/static{property2}', 'Property1Value/staticProperty2Vaeluee'),
			array($object, 'stäticValüe1/staticValue2{property2}staticValue3{property1}staticValue4', 'stäticValüe1/staticValue2Property2VaelueestaticValue3Property1ValuestaticValue4'),
			array($object, '{nonExistingProperty}', ''),
			array($object, '{dateProperty}', '1980-12-13'),
			array($object, '{dateProperty:y}', '80'),
			array($object, '{dateProperty:Y}/{dateProperty:m}/{dateProperty:d}', '1980/12/13'),
			array($object, '{subObject.subObjectProperty}', 'SubObjectPropertyValue'),
		);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @dataProvider createPathSegmentForObjectProvider
	 * @param object $object
	 * @param string $uriPattern
	 * @param string $expectedResult
	 * @return void
	 */
	public function createPathSegmentForObjectTests($object, $uriPattern, $expectedResult) {
		$identityRoutePart = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\IdentityRoutePart', array('dummy'));
		$identityRoutePart->setUriPattern($uriPattern);
		$actualResult = $identityRoutePart->_call('createPathSegmentForObject', $object);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function createPathSegmentForObjectThrowsInvalidUriPatterExceptionIfItSpecifiedPropertiesContainObjects() {
		$identityRoutePart = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\IdentityRoutePart', array('dummy'));
		$object = new \stdClass();
		$object->objectProperty = new \stdClass();
		$identityRoutePart->setUriPattern('{objectProperty}');
		$identityRoutePart->_call('createPathSegmentForObject', $object);
	}
}
?>
