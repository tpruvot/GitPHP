<?php
/**
 * MemoryCache test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Cache
 */
class GitPHP_MemoryCacheTest extends PHPUnit_Framework_TestCase
{
	protected $cache;

	protected function setUp()
	{
		$this->cache = new GitPHP_MemoryCache();
	}

	public function testCache()
	{
		$this->assertFalse($this->cache->Exists('somekey'));

		$this->cache->Set('somekey', 'somevalue');
		$this->assertTrue($this->cache->Exists('somekey'));
		$this->assertEquals('somevalue', $this->cache->Get('somekey'));

		$this->cache->Set('otherkey', 'othervalue');
		$this->assertTrue($this->cache->Exists('otherkey'));

		$this->cache->Delete('somekey');
		$this->assertFalse($this->cache->Exists('somekey'));
		$this->assertNull($this->cache->Get('somekey'));

		$this->cache->Clear();
		$this->assertFalse($this->cache->Exists('otherkey'));
		$this->assertEquals(0, $this->cache->GetCount());
	}

	public function testSize()
	{
		$this->cache->SetSize(50);

		for ($i = 1; $i <= 51; ++$i) {
			$this->cache->Set('testkey' . $i, 'testvalue' . $i);
		}
		$this->assertFalse($this->cache->Exists('testkey1'));

		$this->cache->Clear();

		for ($i = 1; $i <= 50; ++$i) {
			$this->cache->Set('testkey' . $i, 'testvalue' . $i);
		}
		$this->assertEquals('testvalue1', $this->cache->Get('testkey1'));
		$this->cache->Set('testkey51', 'testvalue51');
		$this->assertEquals(50, $this->cache->GetCount());
		$this->assertTrue($this->cache->Exists('testkey1'));
		$this->assertFalse($this->cache->Exists('testkey2'));

		$this->cache->Clear();

		for ($i = 1; $i <= 50; ++$i) {
			$this->cache->Set('testkey' . $i, 'testvalue' . $i);
		}
		$this->cache->Set('testkey1', 'testvalue1');
		$this->cache->Set('testkey51', 'testvalue51');
		$this->assertTrue($this->cache->Exists('testkey1'));
		$this->assertFalse($this->cache->Exists('testkey2'));

		$this->cache->Clear();
		$this->cache->SetSize(0);
	}

	public function testAutoManage()
	{
		$this->cache->SetAutoManaged(true);
		$this->assertTrue($this->cache->GetAutoManaged());

		$this->cache->Set('project|project1', 'project1value');
		$this->assertTrue($this->cache->Exists('project|project1'));

		$this->cache->Set('project|project2', 'project2value');

		$this->assertFalse($this->cache->Exists('project|project1'));
		$this->assertTrue($this->cache->Exists('project|project2'));
		$this->assertEquals(1, $this->cache->GetCount());

		$this->cache->Clear();
	}
}
