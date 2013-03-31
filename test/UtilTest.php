<?php
/**
 * Utility test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test
 */
class GitPHP_UtilTest extends PHPUnit_Framework_TestCase
{
	public function testAddSlash()
	{
		$this->assertEmpty(GitPHP_Util::AddSlash(''));

		$this->assertEquals('/url/with/slash/', GitPHP_Util::AddSlash('/url/with/slash/'));
		$this->assertEquals('/url/without/slash/', GitPHP_Util::AddSlash('/url/without/slash'));

		$this->assertEquals('/url/with/colon:', GitPHP_Util::AddSlash('/url/with/colon:'));
		$this->assertEquals('/', GitPHP_Util::AddSlash('/'));
	}

	public function testAddSlashNix()
	{
		if (GitPHP_Util::IsWindows()) {
			$this->markTestSkipped();
		}

		$this->assertEquals('/path/with/slash/', GitPHP_Util::AddSlash('/path/with/slash/', true));
		$this->assertEquals('/path/without/slash/', GitPHP_Util::AddSlash('/path/without/slash', true));
		$this->assertEquals('/path/with/backslash\\/', GitPHP_Util::AddSlash('/path/with/backslash\\', true));
		$this->assertEquals('/path/with/colon:', GitPHP_Util::AddSlash('/path/with/colon:', true));
		$this->assertEquals('/', GitPHP_Util::AddSlash('/', true));
		$this->assertEquals('\\/', GitPHP_Util::AddSlash('\\', true));
	}

	public function testAddSlashWin()
	{
		if (!GitPHP_Util::IsWindows()) {
			$this->markTestSkipped();
		}

		$this->assertEquals('path\\with\\backslash\\', GitPHP_Util::AddSlash('path\\with\\backslash\\', true));
		$this->assertEquals('path\\without\\backslash\\', GitPHP_Util::AddSlash('path\\without\\backslash', true));
		$this->assertEquals('path\\with\\slash/', GitPHP_Util::AddSlash('path\\with\\slash/', true));
		$this->assertEquals('/path/with/colon:', GitPHP_Util::AddSlash('/path/with/colon:', true));
		$this->assertEquals('\\', GitPHP_Util::AddSlash('\\', true));
		$this->assertEquals('/', GitPHP_Util::AddSlash('/', true));
	}

	public function testMakeSlug()
	{
		$this->assertEquals('some-path', GitPHP_Util::MakeSlug('some/path'));
		$this->assertEquals('somepath', GitPHP_Util::MakeSlug('somepath'));
	}

	public function testBaseNameNix()
	{
		if (GitPHP_Util::IsWindows()) {
			$this->markTestSkipped();
		}

		$this->assertEquals('file', GitPHP_Util::BaseName('/some/path/to/file'));
		$this->assertEquals('file', GitPHP_Util::BaseName('/some/path/to/file/'));
		$this->assertEquals('file', GitPHP_Util::BaseName('/some/path/to/file.ext', '.ext'));
		$this->assertEquals('.extfile', GitPHP_Util::BaseName('/some/path/to/.extfile.ext', '.ext'));
	}

	public function testBaseNameWin()
	{
		if (!GitPHP_Util::IsWindows()) {
			$this->markTestSkipped();
		}

		$this->assertEquals('file', GitPHP_Util::BaseName('some\\path\\to\\file'));
		$this->assertEquals('file', GitPHP_Util::BaseName('some\\path\\to\\file\\'));
		$this->assertEquals('file', GitPHP_Util::BaseName('some\\path\\to\\file.ext', '.ext'));
		$this->assertEquals('.extfile', GitPHP_Util::BaseName('some\\path\\to\\.extfile.ext', '.ext'));
	}

	public function testGeshiFilename()
	{
		$this->assertNull(GitPHP_Util::GeshiFilenameToLanguage('unknownfile'));
		$this->assertEquals('make', GitPHP_Util::GeshiFilenameToLanguage('Makefile'));
		$this->assertEquals('make', GitPHP_Util::GeshiFilenameToLanguage('makefile'));
	}

	public function testListDir()
	{
		$datadir = dirname(__FILE__) . '/resources/testdir';
		$listed = GitPHP_Util::ListDir($datadir);
		$this->assertCount(5, $listed);

		$expected = array(
			$datadir . '/.hiddentestfile',
			$datadir . '/testfile1.txt',
			$datadir . '/testdir2/testfile2.txt',
			$datadir . '/testdir2/testdir4/testfile4.txt',
			$datadir . '/testdir3/testfile3.txt'
		);

		$this->assertCount(0, array_diff($listed, $expected));
		$this->assertCount(0, array_diff($expected, $listed));
	}
}
