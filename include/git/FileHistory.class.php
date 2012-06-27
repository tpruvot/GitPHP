<?php
/**
 * Class to load a file's history
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileHistory implements Iterator
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The commit hash
	 *
	 * @var string
	 */
	protected $commitHash;

	/**
	 * The path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The history
	 *
	 * @var GitPHP_FileDiff[]
	 */
	protected $history = array();

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
	 * @param GitPHP_Commit $commit commit to start history from
	 * @param string $path file path to trace history of
	 * @param GitPHP_GitExe $exe git exe
	 */
	public function __construct($project, $commit, $path, $exe)
	{
		if (!$project)
			throw new Exception('Project is required');

		if (!$commit)
			throw new Exception('Commit is required');

		if (empty($path))
			throw new Exception('Path is required');

		if (!$exe)
			throw new Exception('Git exe is required');

		$this->project = $project;

		$this->commitHash = $commit->GetHash();

		$this->path = $path;

		$this->exe = $exe;
	}

	/**
	 * Gets the project for this file history
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Gets the commit for this file history
	 *
	 * @return GitPHP_Commit commit
	 */
	public function GetCommit()
	{
		return $this->project->GetCommit($this->commitHash);
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
	 * Gets the history
	 *
	 * @return GitPHP_FileDiff[] history data
	 */
	public function GetHistory()
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		return $this->history;
	}

	/**
	 * Loads the history data
	 */
	private function LoadData()
	{
		$this->dataLoaded = true;

		$args = array();
		$args[] = $this->commitHash;
		$args[] = '|';
		$args[] = $this->exe->GetBinary();
		$args[] = '--git-dir=' . $this->project->GetPath();
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
					$history = $this->GetProject()->GetObjectManager()->GetFileDiff($line);
					$history->SetCommitHash($commitHash);
					$this->history[] = $history;
				} catch (Exception $e) {
				}
				$commitHash = null;
			}
		}

	}

	/**
	 * Rewinds the iterator
	 *
	 * @return GitPHP_FileDiff
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
	 * @return GitPHP_FileDiff
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->history);
	}

	/**
	 * Returns the current key
	 *
	 * @return int
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
	 *
	 * @return GitPHP_FileDiff|boolean
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

}
