<?php
/**
 * GitPHP FileHistory
 *
 * Class to represent a file's history
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileHistory class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileHistory implements Iterator
{
	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * commitHash
	 *
	 * Stores the commit hash internally
	 *
	 * @access protected
	 */
	protected $commitHash;

	/**
	 * path
	 *
	 * Stores the path internally
	 *
	 * @access protected
	 */
	protected $path;

	/**
	 * history
	 *
	 * Stores the history data
	 *
	 * @access protected
	 */
	protected $history = array();

	/**
	 * dataLoaded
	 *
	 * Stores whether data has been loaded
	 *
	 * @access protected
	 */
	protected $dataLoaded = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 * @param mixed $commit commit to start history from
	 * @param string $path file path to trace history of
	 */
	public function __construct($project, $commit, $path)
	{
		if (!$project)
			throw new Exception('Project is required');

		if (!$commit)
			throw new Exception('Commit is required');

		if (empty($path))
			throw new Exception('Path is required');

		$this->project = $project;

		$this->commitHash = $commit->GetHash();

		$this->path = $path;
	}

	/**
	 * GetProject
	 *
	 * Gets the project for this file history
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * GetCommit
	 *
	 * Gets the commit for this file history
	 *
	 * @access public
	 * @return mixed commit
	 */
	public function GetCommit()
	{
		return $this->project->GetCommit($this->commitHash);
	}

	/**
	 * GetPath
	 *
	 * Gets the path for this file history
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * GetHistory
	 *
	 * Gets the history
	 *
	 * @access public
	 * @return array history data
	 */
	public function GetHistory()
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		return $this->history;
	}

	/**
	 * LoadData
	 *
	 * Loads the history data
	 *
	 * @access private
	 */
	private function LoadData()
	{
		$this->dataLoaded = true;

		$args = array();
		$args[] = $this->commitHash;
		$args[] = '|';
		$args[] = GitPHP_GitExe::GetInstance()->GetBinary();
		$args[] = '--git-dir=' . $this->project->GetPath();
		$args[] = GIT_DIFF_TREE;
		$args[] = '-r';
		$args[] = '--stdin';
		$args[] = '--';
		$args[] = $this->path;
		
		$historylines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_REV_LIST, $args));

		$commitHash = null;
		foreach ($historylines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40})/', $line, $regs)) {
				$commitHash = $regs[1];
			} else if ($commitHash) {
				try {
					$history = new GitPHP_FileDiff($this->GetProject(), $line);
					$history->SetCommitHash($commitHash);
					$this->history[] = $history;
				} catch (Exception $e) {
				}
				$commitHash = null;
			}
		}

	}

	/**
	 * rewind
	 *
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
	 * current
	 *
	 * Returns the current revision
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->history);
	}

	/**
	 * key
	 *
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
	 * next
	 *
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
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->history) !== null;
	}

}
