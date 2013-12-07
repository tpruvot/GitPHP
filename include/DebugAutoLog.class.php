<?php
/**
 * Debug auto logging class (destructor-based)
 *
 * @author Yuriy Nasretdinov <nasretdinov@gmail.com>
 * @copyright Copyright (c) 2013 Christopher Han
 * @package GitPHP
 */
class GitPHP_DebugAutoLog
{
	private $name;

	public function __construct($name = null)
	{
		if (is_null($name)) {
			if (PHP_VERSION_ID >= 50306)
                                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        else
                                $trace = debug_backtrace();
			if (!isset($trace[1]['class']) || !isset($trace[1]['function'])) {
				throw new InvalidArgumentException("You need to specify name when not in method context");
			}
			$name = $trace[1]['class'] . '::' . $trace[1]['function'];
		}
		$this->name = $name;
		GitPHP_DebugLog::GetInstance()->TimerStart();
	}

	public function __destruct()
	{
		GitPHP_DebugLog::GetInstance()->TimerStop($this->name);
	}
}
