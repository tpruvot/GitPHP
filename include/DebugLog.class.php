<?php
/**
 * Debug logging class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_DebugLog implements GitPHP_Observer_Interface
{
	/**
	 * Stores whether logging is enabled
	 *
	 * @var boolean
	 */
	protected $enabled = false;

	/**
	 * Stores whether benchmarking is enabled
	 *
	 * @var boolean
	 */
	protected $benchmark = false;

	/**
	 * Stores the starting instant
	 *
	 * @var float
	 */
	protected $startTime;

	/**
	 * Stores the starting memory
	 *
	 * @var int
	 */
	protected $startMem;

	/**
	 * Stores the log entries
	 *
	 * @var string[]
	 */
	protected $entries = array();

	/**
	 * Constructor
	 *
	 * @param boolean $enabled whether log should be enabled
	 * @param boolean $benchmark whether benchmarking should be enabled
	 */
	public function __construct($enabled = false, $benchmark = false)
	{
		$this->startTime = microtime(true);
		$this->startMem = memory_get_usage();

		$this->enabled = $enabled;
		$this->benchmark = $benchmark;
	}

	/**
	 * Sets start time
	 *
	 * @param float $start starting microtime
	 */
	public function SetStartTime($start)
	{
		$this->startTime = $start;
	}

	/**
	 * Sets start memory
	 *
	 * @param integer $start starting memory
	 */
	public function SetStartMemory($start)
	{
		$this->startMem = $start;
	}

	/**
	 * Log an entry
	 *
	 * @param string $message message to log
	 */
	public function Log($message)
	{
		if (!$this->enabled)
			return;

		$entry = array();
		
		if ($this->benchmark) {
			$entry['time'] = microtime(true);
			$entry['mem'] = memory_get_usage();
		}

		$entry['msg'] = $message;
		$this->entries[] = $entry;
	}

	/**
	 * Gets whether logging is enabled
	 *
	 * @return boolean true if logging is enabled
	 */
	public function GetEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Sets whether logging is enabled
	 *
	 * @param boolean $enable true if logging is enabled
	 */
	public function SetEnabled($enable)
	{
		$this->enabled = $enable;
	}

	/**
	 * Gets whether benchmarking is enabled
	 *
	 * @return boolean true if benchmarking is enabled
	 */
	public function GetBenchmark()
	{
		return $this->benchmark;
	}

	/**
	 * Sets whether benchmarking is enabled
	 *
	 * @param boolean $bench true if benchmarking is enabled
	 */
	public function SetBenchmark($bench)
	{
		$this->benchmark = $bench;
	}

	/**
	 * Gets log entries
	 *
	 * @return string[] log entries
	 */
	public function GetEntries()
	{
		$data = array();
	
		if ($this->enabled) {

			if ($this->benchmark) {
				$endTime = microtime(true);
				$endMem = memory_get_usage();

				$lastTime = $this->startTime;
				$lastMem = $this->startMem;

				$data[] = 'DEBUG: [' . $this->startTime . '] [' . $this->startMem . ' bytes] Start';

			}

			foreach ($this->entries as $entry) {
				if ($this->benchmark) {
					$data[] = 'DEBUG: [' . $entry['time'] . '] [' . ($entry['time'] - $this->startTime) . ' sec since start] [' . ($entry['time'] - $lastTime) . ' sec since last] [' . $entry['mem'] . ' bytes] [' . ($entry['mem'] - $this->startMem) . ' bytes since start] [' . ($entry['mem'] - $lastMem) . ' bytes since last] ' . $entry['msg'];
					$lastTime = $entry['time'];
					$lastMem = $entry['mem'];
				} else {
					$data[] = 'DEBUG: ' . $entry['msg'];
				}
			}

			if ($this->benchmark) {
				$data[] = 'DEBUG: [' . $endTime . '] [' . ($endTime - $this->startTime) . ' sec since start] [' . ($endTime - $lastTime) . ' sec since last] [' . $endMem . ' bytes] [' . ($endMem - $this->startMem) . ' bytes since start] [' . ($endMem - $lastMem) . ' bytes since last] End';
			}
		}

		return $data;
	}

	/**
	 * Clears the log
	 */
	public function Clear()
	{
		$this->entries = array();
	}

	/**
	 * Notify that observable object changed
	 *
	 * @param GitPHP_Observable_Interface $object object
	 * @param int $changeType type of change
	 * @param array $args argument array
	 */
	public function ObjectChanged($object, $changeType, $args = array())
	{
		if ($changeType !== GitPHP_Observer_Interface::LoggableChange)
			return;

		if (!$this->enabled)
			return;

		if (!isset($args[0]) || empty($args[0]))
			return;

		$msg = $args[0];

		$this->Log($msg);
	}

}
