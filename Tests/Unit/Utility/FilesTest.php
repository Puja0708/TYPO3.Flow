<?php
namespace TYPO3\FLOW3\Tests\Unit\Utility;

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
 * Testcase for the Utility Files class
 *
 */
class FilesTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithoutSlashes() {
		$path = 'foobar';
		$this->assertEquals('foobar', \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithForwardSlashes() {
		$path = 'foo/bar/test/';
		$this->assertEquals('foo/bar/test/', \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithBackwardSlashes() {
		$path = 'foo\\bar\\test\\';
		$this->assertEquals('foo/bar/test/', \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes() {
		$path = 'foo/bar\\test/';
		$this->assertEquals('foo/bar/test/', \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForEmptyPath() {
		$this->assertEquals('', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array()));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForOnePath() {
		$this->assertEquals('foo', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('foo')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForTwoPath() {
		$this->assertEquals('foo/bar', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('foo', 'bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithLeadingSlash() {
		$this->assertEquals('/foo/bar', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('/foo', 'bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithTrailingSlash() {
		$this->assertEquals('foo/bar', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('foo', 'bar/')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash() {
		$this->assertEquals('/foo/bar/bar/foo', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('/foo/bar/', '/bar/foo/')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForBrokenPaths() {
		$this->assertEquals('/foo/bar/bar', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('\\foo/bar\\', '\\bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForEmptyPathArrayElements() {
		$this->assertEquals('foo/bar', \TYPO3\FLOW3\Utility\Files::concatenatePaths(array('foo', '', 'bar')));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithDriveLetterAndBackwardSlashes() {
		$path = 'c:\\foo\\bar\\test\\';
		$this->assertEquals('c:/foo/bar/test/', \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 */
	public function pathsWithProtocol() {
		return array(
			array('file:///foo\\bar', 'file:///foo/bar'),
			array('vfs:///foo\\bar', 'vfs:///foo/bar'),
			array('phar:///foo\\bar', 'phar:///foo/bar')
		);
	}

	/**
	 * @test
	 * @dataProvider pathsWithProtocol
	 */
	public function getUnixStylePathWorksForPathWithProtocol($path, $expected) {
		$this->assertEquals($expected, \TYPO3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForNonExistingFiles() {
		$this->assertFalse(\TYPO3\FLOW3\Utility\Files::is_link('NonExistingPath'));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForExistingFileThatIsNoSymlink() {
		$targetPathAndFilename = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestFile'));
		file_put_contents($targetPathAndFilename, 'some data');
		$this->assertFalse(\TYPO3\FLOW3\Utility\Files::is_link($targetPathAndFilename));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsTrueForExistingSymlink() {
		$targetPathAndFilename = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestFile'));
		file_put_contents($targetPathAndFilename, 'some data');
		$linkPathAndFilename = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestLink'));
		if (file_exists($linkPathAndFilename)) {
			unlink($linkPathAndFilename);
		}
		symlink($targetPathAndFilename, $linkPathAndFilename);
		$this->assertTrue(\TYPO3\FLOW3\Utility\Files::is_link($linkPathAndFilename));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForExistingDirectoryThatIsNoSymlink() {
		$targetPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3FilesTestDirectory')) . '/';
		if (!is_dir($targetPath)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($targetPath);
		}
		$this->assertFalse(\TYPO3\FLOW3\Utility\Files::is_link($targetPath));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsTrueForExistingSymlinkDirectory() {
		$targetPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3FilesTestDirectory'));
		if (!is_dir($targetPath)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($targetPath);
		}
		$linkPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3FilesTestDirectoryLink'));
		if (is_dir($linkPath)) {
			rmdir($linkPath);
		}
		symlink($targetPath, $linkPath);
		$this->assertTrue(\TYPO3\FLOW3\Utility\Files::is_link($linkPath));
	}
}
?>