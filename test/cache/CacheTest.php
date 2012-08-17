<?php
/**
 * Cache test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Cache
 */
class GitPHP_CacheTest extends PHPUnit_Framework_TestCase
{
	public function testFile()
	{
		$cache = new GitPHP_Cache(new GitPHP_Cache_File(GITPHP_CACHEDIR . 'objects', 0, false));
		$cache->Clear();

		$this->assertFalse($cache->Exists('testkey1|testkey2'));
		$cache->Set('testkey1|testkey2', 'testvalue1');
		$this->assertTrue($cache->Exists('testkey1|testkey2'));
		$this->assertEquals('testvalue1', $cache->Get('testkey1|testkey2'));

		$this->assertFalse($cache->Get('testkey3|testkey4'));
		$cache->Set('testkey3|testkey4', 'testvalue2');

		$cache->Delete('testkey1|testkey2');
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$this->assertTrue($cache->Exists('testkey3|testkey4'));

		$cache->Clear();
		$this->assertFalse($cache->Exists('testkey3|testkey4'));
	}

	public function testFileLifetime()
	{
		$cache = new GitPHP_Cache(new GitPHP_Cache_File(GITPHP_CACHEDIR . 'objects', 0, false));
		$cache->Clear();

		$cache->Set('testkey1|testkey2', 'testvalue1', 1);
		sleep(2);
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$cache->SetLifetime(1);
		$cache->Set('testkey3|testkey4', 'testvalue2');
		sleep(2);
		$this->assertFalse($cache->Get('testkey3|testkey4'));
	}

	public function testFileCompressed()
	{
		$cache = new GitPHP_Cache(new GitPHP_Cache_File(GITPHP_CACHEDIR . 'objects', 19, false));
		$cache->Clear();

		$this->assertFalse($cache->Exists('testkey1|testkey2'));
		$cache->Set('testkey1|testkey2', '12345678');
		$this->assertTrue($cache->Exists('testkey1|testkey2'));
		$this->assertEquals('12345678', $cache->Get('testkey1|testkey2'));

		$this->assertFalse($cache->Get('testkey3|testkey4'));
		$cache->Set('testkey3|testkey4', '12345678901234567890');
		$this->assertTrue($cache->Exists('testkey3|testkey4'));
		$this->assertEquals('12345678901234567890', $cache->Get('testkey3|testkey4'));

		$cache->Delete('testkey1|testkey2');
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$this->assertTrue($cache->Exists('testkey3|testkey4'));

		$cache->Clear();
		$this->assertFalse($cache->Exists('testkey3|testkey4'));
	}

	public function testFileIgbinary()
	{
		if (!function_exists('igbinary_serialize')) {
			$this->markTestSkipped();
			return;
		}
		$cache = new GitPHP_Cache(new GitPHP_Cache_File(GITPHP_CACHEDIR . 'objects', 0, true));
		$cache->Clear();

		$this->assertFalse($cache->Exists('testkey1|testkey2'));
		$cache->Set('testkey1|testkey2', '12345678');
		$this->assertTrue($cache->Exists('testkey1|testkey2'));
		$this->assertEquals('12345678', $cache->Get('testkey1|testkey2'));

		$this->assertFalse($cache->Get('testkey3|testkey4'));
		$cache->Set('testkey3|testkey4', '12345678901234567890');
		$this->assertTrue($cache->Exists('testkey3|testkey4'));
		$this->assertEquals('12345678901234567890', $cache->Get('testkey3|testkey4'));

		$cache->Delete('testkey1|testkey2');
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$this->assertTrue($cache->Exists('testkey3|testkey4'));

		$cache->Clear();
		$this->assertFalse($cache->Exists('testkey3|testkey4'));
	}

	public function testMemcache()
	{
		if (!class_exists('Memcache')) {
			$this->markTestSkipped();
			return;
		}
		$cache = new GitPHP_Cache(new GitPHP_Cache_Memcache(array(array('127.0.0.1', 11211))));
		$cache->Clear();

		$this->assertFalse($cache->Exists('testkey1|testkey2'));
		$cache->Set('testkey1|testkey2', 'testvalue1');
		$this->assertTrue($cache->Exists('testkey1|testkey2'));
		$this->assertEquals('testvalue1', $cache->Get('testkey1|testkey2'));

		$this->assertFalse($cache->Get('testkey3|testkey4'));
		$cache->Set('testkey3|testkey4', 'testvalue2');

		$cache->Delete('testkey1|testkey2');
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$this->assertTrue($cache->Exists('testkey3|testkey4'));

		$cache->Clear();
		$this->assertFalse($cache->Exists('testkey3|testkey4'));
	}

	public function testMemcacheLifetime()
	{
		if (!class_exists('Memcache')) {
			$this->markTestSkipped();
			return;
		}
		$cache = new GitPHP_Cache(new GitPHP_Cache_Memcache(array(array('127.0.0.1', 11211))));
		$cache->Clear();

		$cache->Set('testkey1|testkey2', 'testvalue1', 1);
		sleep(2);
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$cache->SetLifetime(1);
		$cache->Set('testkey3|testkey4', 'testvalue2');
		sleep(2);
		$this->assertFalse($cache->Get('testkey3|testkey4'));
	}

	public function testMemcached()
	{
		if (!class_exists('Memcached')) {
			$this->markTestSkipped();
			return;
		}
		$cache = new GitPHP_Cache(new GitPHP_Cache_Memcached(array(array('127.0.0.1', 11211))));
		$cache->Clear();

		$this->assertFalse($cache->Exists('testkey1|testkey2'));
		$cache->Set('testkey1|testkey2', 'testvalue1');
		$this->assertTrue($cache->Exists('testkey1|testkey2'));
		$this->assertEquals('testvalue1', $cache->Get('testkey1|testkey2'));

		$this->assertFalse($cache->Get('testkey3|testkey4'));
		$cache->Set('testkey3|testkey4', 'testvalue2');

		$cache->Delete('testkey1|testkey2');
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$this->assertTrue($cache->Exists('testkey3|testkey4'));

		$cache->Clear();
		$this->assertFalse($cache->Exists('testkey3|testkey4'));
	}

	public function testMemcachedLifetime()
	{
		if (!class_exists('Memcached')) {
			$this->markTestSkipped();
			return;
		}
		$cache = new GitPHP_Cache(new GitPHP_Cache_Memcached(array(array('127.0.0.1', 11211))));
		$cache->Clear();

		$cache->Set('testkey1|testkey2', 'testvalue1', 1);
		sleep(2);
		$this->assertFalse($cache->Exists('testkey1|testkey2'));

		$cache->SetLifetime(1);
		$cache->Set('testkey3|testkey4', 'testvalue2');
		sleep(2);
		$this->assertFalse($cache->Get('testkey3|testkey4'));
	}
}
