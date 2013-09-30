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
	 * Stores the timers
	 *
	 * @var float[]
	 */
	protected $timers = array();

	/**
	 * @return GitPHP_DebugLog
	 */
	public static function GetInstance()
	{
		static $instance;
		if (!$instance) $instance = new self();
		return $instance;
	}

	/**
	 * You must use GetInstance()
	 */
	private function __construct()
	{
	}

	/**
	 * Constructor
	 *
	 * @param boolean $enabled whether log should be enabled
	 * @param boolean $benchmark whether benchmarking should be enabled
	 */
	public function init($enabled = false, $benchmark = false)
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
	 * Shortcut to start timer
	 */
	public function TimerStart()
	{
		if (!$this->benchmark) return;
		$this->Log('', '', 'start');
	}

	/**
	 * Shortcut to stop timer
	 *
	 * @param $msg
	 * @param $msg_data
	 */
	public function TimerStop($msg, $msg_data = '')
	{
		if (!$this->benchmark) return;
		$this->Log($msg, $msg_data, 'stop');
	}

	/**
	 * Log an entry
	 *
	 * @param string $msg message to log
	 */
	public function Log($msg, $msg_data = '', $type = 'ts')
	{
		if (!$this->enabled)
			return;

		$entry = array();

		if ($type == 'start') {
			array_push($this->timers, microtime(true));
			return;
		} else if ($type == 'stop') {
			$timer = array_pop($this->timers);
			$entry['time'] = $duration = microtime(true) - $timer;
			foreach ($this->timers as &$item) $item += $duration;
		} else {
			if ($this->benchmark) {
				$entry['time'] = (microtime(true) - $this->startTime);
				$entry['reltime'] = true;
				$entry['mem'] = memory_get_usage();
			}
		}

		$entry['name'] = $msg;
		$entry['value'] = $msg_data;
		$bt = explode("\n", new Exception());
		array_shift($bt);
		array_shift($bt);
		$entry['bt'] = implode("\n", $bt);
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
	 * Clears the log
	 */
	public function Clear()
	{
		$this->entries = array();
	}

	/**
	 * Gets the log entries
	 *
	 * @return array entry data
	 */
	public function GetEntries()
	{
		return $this->entries;
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
		$msg_data = !empty($args[1]) ? $args[1] : '';
		$type = !empty($args[2]) ? $args[2] : 'ts';

		$this->Log($msg, $msg_data, $type);
	}

}
