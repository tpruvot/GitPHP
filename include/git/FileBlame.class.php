<?php
/**
 * Class to get a file's blame
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileBlame
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
	 * The file path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Blame data
	 *
	 * @var array
	 */
	protected $blame = array();

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
	 * @param GitPHP_Commit $commit commit to trace blame from
	 * @param string $path file path to trace blame of
	 * @param GitPHP_GitExe $exe git executable
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
	 * Gets the blame
	 *
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
	 * Loads the blame data
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

		$blamelines = explode("\n", $this->exe->Execute($this->project->GetPath(), GIT_BLAME, $args));

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
