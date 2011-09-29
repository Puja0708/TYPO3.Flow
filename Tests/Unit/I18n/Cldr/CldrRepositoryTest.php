<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\Cldr;

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
 * Testcase for the CldrRepository
 *
 */
class CldrRepositoryTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\CldrRepository
	 */
	protected $repository;

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @return void
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		$this->repository = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Cldr\CldrRepository', array('dummy'));
		$this->repository->_set('cldrBasePath', 'vfs://Foo/');

		$this->dummyLocale = new \TYPO3\FLOW3\I18n\Locale('en');
	}

	/**
	 * @test
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function modelIsReturnedCorrectlyForSingleFile() {
		file_put_contents('vfs://Foo/Bar.xml', '');

			// Second access should not invoke objectManager request
		$result = $this->repository->getModel('Bar');
		$this->assertAttributeContains('vfs://Foo/Bar.xml', 'sourcePaths',  $result);

		$result = $this->repository->getModel('NoSuchFile');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function modelIsReturnedCorrectlyForGroupOfFiles() {
		mkdir('vfs://Foo/Directory');
		file_put_contents('vfs://Foo/Directory/en.xml', '');

		$mockLocalizationService = $this->getMock('TYPO3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getParentLocaleOf')->will($this->returnValue(NULL));

		$this->repository->injectLocalizationService($mockLocalizationService);

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
		$this->assertAttributeContains('vfs://Foo/Directory/root.xml', 'sourcePaths',  $result);
		$this->assertAttributeContains('vfs://Foo/Directory/en.xml', 'sourcePaths',  $result);

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'NoSuchDirectory');
		$this->assertEquals(FALSE, $result);
	}
}

?>