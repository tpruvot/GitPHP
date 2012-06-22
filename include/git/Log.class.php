<?php
/**
 * Class to represent a project log
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Log extends GitPHP_RevList
{
	/**
	 * Whether this log is operating in compatibility mode
	 *
	 * @var boolean
	 */
	protected $compat = false;

	/**
	 * The threshold at which log skips will fallback to the git executable
	 *
	 * @var int
	 */
	protected $skipFallback = 200;

	/**
	 * Gets whether this log is operating in compatibilty mode
	 *
	 * @return bool true if compatibility mode is on
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * Sets whether this log is operating in compatibility mode
	 *
	 * @param bool $compat true if compatibility mode is on
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;
	}

	/**
	 * Gets the threshold at which log skips will fallback on the git executable
	 *
	 * @return int skip fallback number
	 */
	public function GetSkipFallback()
	{
		return $this->skipFallback;
	}

	/**
	 * Sets the threshold at which log skips will fallback on the git executable
	 *
	 * @param int $skip skip fallback number
	 */
	public function SetSkipFallback($skip)
	{
		$this->skipFallback = $skip;
	}

	/**
	 * Loads the data for this log
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if ($this->compat || ($this->skip > $this->skipFallback)) {
			$this->hashList = $this->RevList();
		} else {
			$this->hashList = $this->RawLog();
		}
	}

	/**
	 * Loads the log using raw git objects
	 *
	 * Based on history walking code from glip
	 *
	 * @return string[] array of hashes
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
	 * Filters out commits older than a certain age
	 *
	 * @param int $age age in seconds
	 * @param int $min minimum number of commits to retain
	 */
	public function FilterOldCommits($age, $min = 0)
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		if (count($this->hashList) > $min) {
			for ($i = $min; $i < count($this->hashList); ++$i) {
				$commit = $this->project->GetCommit($this->hashList[$i]);
				if ((time() - $commit->GetCommitterEpoch()) > $age) {
					$this->hashList = array_slice($this->hashList, 0, $i);
					break;
				}
			}
		}

	}
}
