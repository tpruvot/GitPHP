<?php
/**
 * Interface for log data load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Log
 */
interface GitPHP_LogLoadStrategy_Interface
{
	/**
	 * Gets the data for a log
	 *
	 * @param GitPHP_Log $log log
	 * @return string[] hash array
	 */
	public function Load($log);
}
