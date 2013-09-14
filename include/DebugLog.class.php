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
		$msg_data = isset($args[1]) ? $args[1] : '';
		$type = isset($args[2]) ? $args[2] : 'ts';

		$this->Log($msg, $msg_data, $type);
	}

	public function PrintHtml()
	{
		if (!$this->enabled) return;

		foreach ($this->entries as $i => $e) {
			$bt_id = 'bt_' . $i;
			if (strlen($e['value']) > 512) {
				$contents  = htmlspecialchars(substr($e['value'], 0, 512) . "...");
				$contents .= "\n\n<i>" . (strlen($e['value']) - 512) . " bytes more in output</i>";
			} else {
				$contents = htmlspecialchars($e['value']);
			}
			echo "<tr>
				<td class='debug_key'>$e[name]</td>
				<td class='debug_value'>
					" . nl2br($contents) . ($contents != "" ? "<br§ />" : "") . "
					<span class='debug_toggle' onclick='bt_toggle(\"$bt_id\");'>trace</span>&nbsp;
					<div style='display: none;' class='debug_bt' id='$bt_id'>$e[bt]</div>
				</td>
				<td class='debug_time'>
					" . ($e['time'] ? sprintf("%.1f", $e['time'] * 1000) : '') . "
					" . ($e['time'] ? (!empty($e['reltime']) ? " ms from start" : " ms") : '') . "
				</td>
			</tr>";
		}
	}

	public function PrintHtmlHeader()
	{
		if (!$this->enabled) return;

		echo
<<<HEREDOC
		<script type="text/javascript">
			function bt_toggle(id) {
				var el = document.getElementById(id);
				el.style.display = ((el.style.display == 'none') ? 'block' : 'none');
			}
		</script>
		<style type="text/css">
			.debug {
				border: 0;
				border-spacing: 0;
				width: 100%;
			}
			.debug_toggle {
				color: #88a; border-bottom: 1px dashed blue;
				display: inline-block;
				margin: 3px;
				cursor: pointer;
			}
			.debug_key {
				background: #ccf; border-bottom: 1px solid #888;
				max-width: 100px;
				word-wrap: break-word;
			}
			.debug_value {
				background: #ccc; border-bottom: 1px solid #888;
				max-width: 900px;
				word-wrap: break-word;
			}
			.debug_bt {
				white-space: pre;
			}
			.debug_time {
				background: #cff; border-bottom: 1px solid #888;
			}
		</style>
		<table class="debug"><tbody>
HEREDOC;
	}

	public function PrintHtmlFooter()
	{
		if (!$this->enabled) return;
		echo '</tbody></table>';
	}
}
