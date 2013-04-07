<?php
/**
 * Class to load a file's history
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileHistory implements Iterator, GitPHP_Pagination_Interface
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project = null;

	/**
	 * History
	 *
	 * @var GitPHP_FileDiff[]
	 */
	protected $history = array();

	/**
	 * The path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The limit of objects to load
	 *
	 * @var int
	 */
	protected $limit = 50;

	/**
	 * The number of objects to skip
	 *
	 * @var int
	 */
	protected $skip = 0;

	/**
	 * The hash to walk back from
	 *
	 * @var string
	 */
	protected $hash = false;

	/**
	 * Whether data has been loaded
	 *
	 * @var boolean
	 */
	protected $dataLoaded = false;

	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param string $path file path to trace history of
	 * @param GitPHP_GitExe $exe git exe
	 * @param GitPHP_Commit $head commit to start history from
	 * @param int $limit limit of revisions to walk
	 * @param int $skip number of revisions to skip
	 */
	public function __construct($project, $path, $exe, $head = null, $limit = 0, $skip = 0)
	{
		if (!$project) {
			throw new Exception('Project is required');
		}

		$this->project = $project;
		$this->limit = $limit;
		$this->skip = $skip;

		if (!$head)
			$head = $this->project->GetHeadCommit();

		if ($head) {
			$this->hash = $head->GetHash();
		}
		if (empty($path))
			throw new Exception('Path is required');

		if (!$exe)
			throw new Exception('Git exe is required');

		$this->path = $path;

		$this->exe = $exe;
	}

	/**
	 * Gets the path for this file history
	 *
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * Gets the project
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Gets the count
	 *
	 * @return int count
	 */
	public function GetCount()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return count($this->history);
	}

	/**
	 * Gets the limit
	 *
	 * @return int limit
	 */
	public function GetLimit()
	{
		return $this->limit;
	}

	/**
	 * Sets the limit
	 *
	 * @param int $limit limit
	 */
	public function SetLimit($limit)
	{
		if ($this->limit == $limit)
			return;

		if ($this->dataLoaded) {
			if (($limit < $this->limit) && ($limit > 0)) {
				/* want less data, just trim the array */
				$this->history = array_slice($this->history, 0, $limit);
			} else if (($limit > $this->limit) || ($limit < 1)) {
				/* want more data, have to reload */
				$this->Clear();
			}
		}

		$this->limit = $limit;
	}

	/**
	 * Gets the skip number
	 *
	 * @return int skip number
	 */
	public function GetSkip()
	{
		return $this->skip;
	}

	/**
	 * Sets the skip number
	 *
	 * @param int $skip skip number
	 */
	public function SetSkip($skip)
	{
		if ($skip == $this->skip)
			return;

		if ($this->dataLoaded) {
			$this->Clear();
		}

		$this->skip = $skip;
	}

	/**
	 * Gets the head this log will walk from
	 *
	 * @return GitPHP_Commit head commit
	 */
	public function GetHead()
	{
		return $this->project->GetCommit($this->hash);
	}

	/**
	 * Sets the head this log will walk from
	 *
	 * @param GitPHP_Commit $head head commit
	 */
	public function SetHead($head)
	{
		if ($head)
			$this->SetHeadHash($head->GetHash());
		else
			$this->SetHeadHash(null);
	}

	/**
	 * Gets the head hash this log will walk from
	 *
	 * @return string hash
	 */
	public function GetHeadHash()
	{
		return $this->hash;
	}

	/**
	 * Sets the head hash this log will walk from
	 *
	 * @param string $hash head commit hash
	 */
	public function SetHeadHash($hash)
	{
		if (empty($hash)) {
			$head = $this->project->GetHeadCommit();
			if ($head)
				$hash = $head->GetHash();
		}

		if ($hash != $this->hash) {
			$this->Clear();
			$this->hash = $hash;
		}
	}

	/**
	 * Loads the history data
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		$args = array();
		$args[] = $this->hash;
		$args[] = '--no-merges';

		$canSkip = true;
		if ($this->skip > 0)
			$canSkip = $this->exe->CanSkip();

		if ($canSkip) {
			if ($this->limit > 0) {
				$args[] = '--max-count=' . $this->limit;
			}
			if ($this->skip > 0) {
				$args[] = '--skip=' . $this->skip;
			}
		} else {
			if ($this->limit > 0) {
				$args[] = '--max-count=' . ($this->limit + $this->skip);
			}
		}

		$args[] = '--';
		$args[] = $this->path;
		$args[] = '|';
		$args[] = $this->exe->GetBinary();
		$args[] = '--git-dir=' . escapeshellarg($this->project->GetPath());
		$args[] = GIT_DIFF_TREE;
		$args[] = '-r';
		$args[] = '--stdin';
		$args[] = '--';
		$args[] = $this->path;
		
		$historylines = explode("\n", $this->exe->Execute($this->project->GetPath(), GIT_REV_LIST, $args));

		$commitHash = null;
		foreach ($historylines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40})/', $line, $regs)) {
				$commitHash = $regs[1];
			} else if ($commitHash) {
				try {
					$this->history[] = array('diffline' => $line, 'commithash' => $commitHash);
				} catch (Exception $e) {
				}
				$commitHash = null;
			}
		}

		if (($this->skip > 0) && (!$canSkip)) {
			if ($this->limit > 0) {
				$this->history = array_slice($this->history, $this->skip, $this->limit);
			} else {
				$this->history = array_slice($this->history, $this->skip);
			}
		}

	}

	/**
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->history);
	}

	/**
	 * Returns the current revision
	 *
	 * @return GitPHP_Commit
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		$data = current($this->history);

		if (!(empty($data['diffline']) || empty($data['commithash']))) {
			$history = $this->GetProject()->GetObjectManager()->GetFileDiff($data['diffline']);
			$history->SetCommitHash($data['commithash']);
			return $history;
		}

		return null;
	}

	/**
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->history);
	}

	/**
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->history);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->history) !== null;
	}

	/**
	 * Clears the loaded data
	 */
	public function Clear()
	{
		if (!$this->dataLoaded)
			return;

		$this->history = array();
		reset($this->history);

		$this->dataLoaded = false;
	}
}
