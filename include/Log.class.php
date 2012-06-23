<?php
/**
 * Logging class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Log
{
	/**
	 * Stores the singleton instance
	 * @deprecated
	 */
	protected static $instance;

	/**
	 * Stores whether logging is enabled
	 */
	protected $enabled = false;

	/**
	 * Stores whether benchmarking is enabled
	 */
	protected $benchmark = false;

	/**
	 * Stores the starting instant
	 */
	protected $startTime;

	/**
	 * Stores the starting memory
	 */
	protected $startMem;

	/**
	 * Stores the log entries
	 */
	protected $entries = array();

	/**
	 * Returns the singleton instance
	 * @deprecated
	 *
	 * @return mixed instance of logging class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Log();
		}

		return self::$instance;
	}

	/**
	 * Releases the singleton instance
	 * @deprecated
	 */
	public static function DestroyInstance()
	{
		self::$instance = null;
	}

	/**
	 * Constructor
	 *
	 * @return Log object
	 */
	public function __construct()
	{
		$this->startTime = microtime(true);
		$this->startMem = memory_get_usage();

		$this->enabled = GitPHP_Config::GetInstance()->GetValue('debug', false);
		$this->benchmark = GitPHP_Config::GetInstance()->GetValue('benchmark', false);

		if (!self::$instance) {
			self::$instance = $this;
		}
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

	protected function GetTime($time, $since = 0.0)
	{
		return sprintf('%.6F', $time - $since);
	}

	protected function GetMem($mem, $since = 0)
	{
		return ($mem - $since);
	}

	/**
	 * Calculates times and gets log entries
	 *
	 * @return array log entries
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

				$data[] = 'DEBUG: [' . $this->GetTime($this->startTime) . '] [' . $this->GetMem($this->startMem) . ' bytes] Start';

			}

			foreach ($this->entries as $entry) {
				if ($this->benchmark) {
					$data[] = 'DEBUG: [' . $this->GetTime($entry['time']) . '] '.
						'[' . $this->GetTime($entry['time'], $this->startTime) . ' sec since start] ' .
						'[' . $this->GetTime($entry['time'], $lastTime) . ' sec since last] ' .
						'[' . $this->GetMem($entry['mem']) . ' bytes] '.
						'[' . $this->GetMem($entry['mem'], $this->startMem) . ' bytes since start] '.
						'[' . $this->GetMem($entry['mem'], $lastMem) . ' bytes since last] ' . $entry['msg'];
					$lastTime = $entry['time'];
					$lastMem = $entry['mem'];
				} else {
					$data[] = 'DEBUG: ' . $entry['msg'];
				}
			}

			if ($this->benchmark) {
				$data[] = 'DEBUG: [' . $this->GetTime($endTime) . '] '.
					'[' . $this->GetTime($endTime, $this->startTime) . ' sec since start] '.
					'[' . $this->GetTime($endTime, $lastTime) . ' sec since last] '.
					'[' . $this->GetMem($endMem) . ' bytes] '.
					'[' . $this->GetMem($endMem - $this->startMem) . ' bytes since start] '.
					'[' . $this->GetMem($endMem, $lastMem) . ' bytes since last] End';
			}
		}

		return $data;
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
