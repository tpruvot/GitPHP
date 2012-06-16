<?php
/**
 * GitPHP Log
 *
 * Class to represent a project log
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'RevList.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Commit.class.php');

/**
 * Log class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Log extends GitPHP_RevList
{
	/**
	 * compat
	 *
	 * Stores whether this log is operating in compatibility mode
	 *
	 * @access protected
	 */
	protected $compat = false;

	/**
	 * skipFallback
	 *
	 * Stores the threshold at which log skips will
	 * fallback to the git executable
	 *
	 * @access protected
	 */
	protected $skipFallback = 200;

	/**
	 * GetCompat
	 *
	 * Gets whether this log is operating in compatibilty mode
	 *
	 * @access public
	 * @return bool true if compatibility mode is on
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * SetCompat
	 *
	 * Sets whether this log is operating in compatibility mode
	 *
	 * @access public
	 * @param bool $compat true if compatibility mode is on
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;
	}

	/**
	 * GetSkipFallback
	 *
	 * Gets the threshold at which log skips will fallback on
	 * the git executable
	 *
	 * @access public
	 * @return int skip fallback number
	 */
	public function GetSkipFallback()
	{
		return $this->skipFallback;
	}

	/**
	 * SetSkipFallback
	 *
	 * Sets the threshold at which log skips will fallback on
	 * the git executable
	 *
	 * @access public
	 * @param int $skip skip fallback number
	 */
	public function SetSkipFallback($skip)
	{
		$this->skipFallback = $skip;
	}

	/**
	 * LoadData
	 *
	 * Loads the data for this log
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if ($this->compat || ($this->skip > $this->skipFallback)) {
			$this->revList = $this->RevList();
		} else {
			$this->revList = $this->RawLog();
		}
	}

	/**
	 * RawLog
	 *
	 * Loads the log using raw git objects
	 * Based on history walking code from glip
	 *
	 * @access private
	 * @return array array of hashes
	 */
	private function RawLog()
	{
		$total = $this->limit + $this->skip;

		$inc = array();
		$num = 0;
		$queue = array($this->project->GetCommit($this->hash));
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

		$queue = array($this->project->GetCommit($this->hash));
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

		if ($this->skip > 0) {
			$commitLog = array_slice($commitLog, $this->skip, $this->limit);
		}
		usort($commitLog, array('GitPHP_Commit', 'CompareAge'));

		$hashLog = array();
		for ($i = 0; $i < count($commitLog); ++$i) {
			$hashLog[] = $commitLog[$i]->GetHash();
		}

		return $hashLog;
	}

	/**
	 * FilterOldCommits
	 *
	 * Filters out commits older than a certain age
	 *
	 * @access public
	 * @param int $age age in seconds
	 * @param int $min minimum number of commits to retain
	 */
	public function FilterOldCommits($age, $min = 0)
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		if (count($this->revList) > $min) {
			for ($i = $min; $i < count($this->revList); ++$i) {
				$commit = $this->project->GetCommit($this->revList[$i]);
				if ((time() - $commit->GetCommitterEpoch()) > $age) {
					$this->revList = array_slice($this->revList, 0, $i);
					break;
				}
			}
		}

	}
}
