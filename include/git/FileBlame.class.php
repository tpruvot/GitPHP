<?php
/**
 * GitPHP FileBlame
 *
 * Class to get a file's blame
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileBlame class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileBlame
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
	 * blame
	 * 
	 * Stores the blame data
	 *
	 * @access protected
	 */
	protected $blame = array();

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
	 * @param mixed $commit commit to trace blame from
	 * @param string $path file path to trace blame of
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
	 * GetBlame
	 *
	 * Gets the blame
	 *
	 * @access public
	 * @return array blame data
	 */
	public function GetBlame()
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		$blame = $this->blame;

		foreach ($blame as $line => $hash) {
			$blame[$line] = $this->project->GetCommit($hash);
		}

		return $blame;
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
		$args[] = '-s';
		$args[] = '-l';
		$args[] = $this->commitHash;
		$args[] = '--';
		$args[] = $this->path;

		$blamelines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_BLAME, $args));

		$lastcommit = '';
		foreach ($blamelines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40})(\s+.+)?\s+([0-9]+)\)/', $line, $regs)) {
				if ($regs[1] != $lastcommit) {
					$this->blame[(int)($regs[3])] = $regs[1];
					$lastcommit = $regs[1];
				}
			}
		}
	}

}
