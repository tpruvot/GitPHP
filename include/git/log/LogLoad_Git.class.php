<?php
/**
 * Log load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Log
 */
class GitPHP_LogLoad_Git extends GitPHP_RevList_Git implements GitPHP_LogLoadStrategy_Interface
{
	/**
	 * Gets the data for a log
	 *
	 * @param GitPHP_Log $log log
	 * @return string[] hash array
	 */
	public function Load($log)
	{
		if (!$log)
			return;

		return $this->RevList($log->GetProject(), $log->GetHeadHash(), $log->GetLimit(), $log->GetSkip());
	}
}
