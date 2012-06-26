<?php
/**
 * Log load strategy using raw objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Log
 */
class GitPHP_LogLoad_Raw implements GitPHP_LogLoadStrategy_Interface
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

		$total = $log->GetLimit() + $log->GetSkip();

		$head = $log->GetHead();

		$inc = array();
		$num = 0;
		$queue = array($head);
		while (($commit = array_shift($queue)) !== null) {
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (!isset($inc[$parent->GetHash()])) {
					$inc[$parent->GetHash()] = 1;
					$queue[] = $parent;
					$num++;
				} else {
					$inc[$parent->GetHash()]++;
				}
			}
			if ($num >= $total)
				break;
		}

		$queue = array($head);
		$commitLog = array();
		$num = 0;
		while (($commit = array_pop($queue)) !== null) {
			array_push($commitLog, $commit);
			$num++;
			if ($num == $total) {
				break;
			}
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (isset($inc[$parent->GetHash()])) {
					if (--$inc[$parent->GetHash()] == 0) {
						$queue[] = $parent;
					}
				}
			}
		}

		if ($log->GetSkip() > 0) {
			$commitLog = array_slice($commitLog, $log->GetSkip(), $log->GetLimit());
		}
		usort($commitLog, array('GitPHP_Commit', 'CompareAge'));

		$hashLog = array();
		for ($i = 0; $i < count($commitLog); ++$i) {
			$hashLog[] = $commitLog[$i]->GetHash();
		}

		return $hashLog;
	}
}
