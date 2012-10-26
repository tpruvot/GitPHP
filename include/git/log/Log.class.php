<?php
/**
 * Class to represent a project log
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Log
 */
class GitPHP_Log extends GitPHP_RevList
{
	/**
	 * Data load strategy
	 *
	 * @var GitPHP_LogLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_Commit $head head to walk back from
	 * @param GitPHP_LogLoadStrategy_Interface $strategy log load strategy
	 * @param int $limit limit of revisions to walk
	 * @param int $skip number of revisions to skip
	 */
	public function __construct($project, $head = null, GitPHP_LogLoadStrategy_Interface $strategy, $limit = 50, $skip = 0)
	{
		parent::__construct($project, $head, $limit, $skip);

		if (!$strategy)
			throw new Exception('Log load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set data load strategy
	 *
	 * @param GitPHP_LogLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_LogLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Loads the data for this log
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if (empty($this->hash))
			return;

		$this->hashList = $this->strategy->Load($this);
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

	/**
	 * Filters out commits matching a certain pattern
	 *
	 * @param string $pattern pattern
	 */
	public function FilterCommits($pattern)
	{
		if (empty($pattern))
			return;

		if (!$this->dataLoad) {
			$this->LoadData();
		}

		$filtered = false;
		foreach ($this->hashList as $i => $hash) {
			$commit = $this->project->GetCommit($hash);
			$comment = $commit->GetComment();
			foreach ($comment as $commentline) {
				if (preg_match($pattern, $commentline)) {
					unset($this->hashList[$i]);
					$filtered = true;
					break;
				}
			}
		}

		if ($filtered) {
			$this->hashList = array_values($this->hashList);
		}
	}
}
