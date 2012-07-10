<?php
/**
 * Config test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test
 */
class GitPHP_ConfigTest extends PHPUnit_Framework_TestCase
{
	protected $config;

	protected function setUp()
	{
		$this->config = new GitPHP_Config();
	}

	public function testDefaults()
	{
		$this->assertTrue($this->config->HasKey('locale'));
		$this->assertEquals('en_US', $this->config->GetValue('locale'));
	}

	public function testGetAndSet()
	{
		$this->assertFalse($this->config->HasKey('testkey'));
		$this->config->SetValue('testkey', 'testvalue');
		$this->assertTrue($this->config->HasKey('testkey'));
		$this->assertEquals('testvalue', $this->config->GetValue('testkey'));
		$this->config->SetValue('testkey', null);
		$this->assertFalse($this->config->HasKey('testkey'));
		$this->assertNull($this->config->GetValue('testkey'));
	}

	public function testClear()
	{
		$this->config->SetValue('testkey2', 'testvalue');
		$this->assertTrue($this->config->HasKey('testkey2'));
		$this->config->ClearConfig();
		$this->assertFalse($this->config->HasKey('testkey2'));
	}

}
