<?php
/**
 * DebugLog test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test
 */
class GitPHP_DebugLogTest extends PHPUnit_Framework_TestCase
{
	protected $log;

	protected function setUp()
	{
	//	$this->log = new GitPHP_DebugLog();
	}

	public function testLog()
	{
	/*
		$this->assertFalse($this->log->GetEnabled());
		$this->log->Log('Test log message');
		$this->log->ObjectChanged(null, GitPHP_Observer_Interface::LoggableChange, array('Test log message'));
		$this->assertCount(0, $this->log->GetEntries());

		$this->log->SetEnabled(true);
		$this->assertTrue($this->log->GetEnabled());

		$this->log->Log('Test log message');
		$this->log->ObjectChanged(null, GitPHP_Observer_Interface::LoggableChange, array('Test log message 2'));
		$data = $this->log->GetEntries();
		$this->assertCount(2, $data);
		$this->assertEquals('DEBUG: Test log message', $data[0]);
		$this->assertEquals('DEBUG: Test log message 2', $data[1]);
		
		$this->log->Clear();
		$this->assertCount(0, $this->log->GetEntries());

		$this->log->SetEnabled(false);
		$this->assertFalse($this->log->GetEnabled());
	*/
	}

	public function testBenchmark()
	{
	/*
		$this->log->SetEnabled(true);
		$this->assertFalse($this->log->GetBenchmark());
		$this->log->SetBenchmark(true);
		$this->assertTrue($this->log->GetBenchmark());

		$this->log->Log('Test log message');

		$data = $this->log->GetEntries();

		$this->assertCount(3, $data);
		$this->assertRegExp('/^DEBUG: \[[0-9]+(\.[0-9]+)?\] \[[0-9]+ bytes\] Start$/', $data[0]);
		$this->assertRegExp('/^DEBUG: \[[0-9]+(\.[0-9]+)?\] \[[0-9]+(\.[0-9]+)?(E-[0-9]+)? sec since start\] \[[0-9]+(\.[0-9]+)?(E-[0-9]+)? sec since last\] \[[0-9]+ bytes\] \[[0-9]+ bytes since start\] \[[0-9]+ bytes since last\] Test log message$/', $data[1]);
		$this->assertRegExp('/^DEBUG: \[[0-9]+(\.[0-9]+)?\] \[[0-9]+(\.[0-9]+)?(E-[0-9]+)? sec since start\] \[[0-9]+(\.[0-9]+)?(E-[0-9]+)? sec since last\] \[[0-9]+ bytes\] \[[0-9]+ bytes since start\] \[[0-9]+ bytes since last\] End$/', $data[2]);

		$this->log->Clear();
		$this->log->SetBenchmark(false);
		$this->assertFalse($this->log->GetBenchmark());
		$this->log->SetEnabled(false);
	*/
	}

}
