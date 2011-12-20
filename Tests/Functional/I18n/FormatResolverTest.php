<?php
namespace TYPO3\FLOW3\Tests\Functional\I18n;

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
 * Testcase for the I18N placeholder replacing
 *
 */
class FormatResolverTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\FormatResolver
	 */
	protected $formatResolver;

	/**
	 * Initialize dependencies
	 */
	public function setUp() {
		parent::setUp();
		$this->formatResolver = $this->objectManager->get('TYPO3\FLOW3\I18n\FormatResolver');
	}

	/**
	 * @return array
	 */
	public function placeholderAndDateValues() {
		$date = new \DateTime('@1322228231');
		return array(
			array('{0,datetime,date,short}', array($date), new \TYPO3\FLOW3\I18n\Locale('de'), '25.11.11'),
			array('{0,datetime,date,short}', array($date), new \TYPO3\FLOW3\I18n\Locale('en'), '11/25/11'),
			array('{0,datetime,time,full}', array($date), new \TYPO3\FLOW3\I18n\Locale('de'), '13:37:11 +00:00'),
			array('{0,datetime,dateTime,short}', array($date), new \TYPO3\FLOW3\I18n\Locale('en'), '11/25/11 1:37 p.m.')
		);
	}

	/**
	 * @test
	 * @dataProvider placeholderAndDateValues
	 */
	public function formatResolverWithDatetimeReplacesCorrectValues($stringWithPlaceholders, $arguments, $locale, $expected) {
		$result = $this->formatResolver->resolvePlaceholders($stringWithPlaceholders, $arguments, $locale);
		$this->assertEquals($expected, $result);
	}

}
?>