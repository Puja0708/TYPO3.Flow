<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\Xml;

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
 * Testcase for the AbstractXmlParser class
 *
 */
class AbstractXmlParserTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function invokesDoParsingFromRootMethodForActualParsing() {
		$sampleXmlFilePath = __DIR__ . '/../Fixtures/MockCldrData.xml';

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Xml\AbstractXmlParser', array('doParsingFromRoot'));
		$parser->expects($this->once())->method('doParsingFromRoot');
		$parser->getParsedData($sampleXmlFilePath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\Xml\Exception\InvalidXmlFileException
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function throwsExceptionWhenBadFilenameGiven() {
		$mockFilenamePath = 'foo';

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Xml\AbstractXmlParser', array('doParsingFromRoot'));
		$parser->getParsedData($mockFilenamePath);
	}
}

?>