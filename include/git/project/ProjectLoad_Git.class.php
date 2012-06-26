<?php
/**
 * Project load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Project
 */
class GitPHP_ProjectLoad_Git implements GitPHP_ProjectLoadStrategy_Interface
{
	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Exe $exe git exe
	 */
	public function __construct($exe)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;
	}

	/**
	 * Load a project's epoch
	 *
	 * @param GitPHP_Project $project project
	 * @return string epoch
	 */
	public function LoadEpoch($project)
	{
		if (!$project)
			return;

		$args = array();
		$args[] = '--format="%(committer)"';
		$args[] = '--sort=-committerdate';
		$args[] = '--count=1';
		$args[] = 'refs/heads';

		$epochstr = trim($this->exe->Execute($project->GetPath(), GIT_FOR_EACH_REF, $args));

		if (preg_match('/ (\d+) [-+][01]\d\d\d$/', $epochstr, $regs)) {
			return $regs[1];
		}
	}

	/**
	 * Load a project's head hash
	 *
	 * @param GitPHP_Project $project
	 * @return string head hash
	 */
	public function LoadHead($project)
	{
		if (!$project)
			return;

		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		return trim($this->exe->Execute($project->GetPath(), GIT_REV_PARSE, $args));
	}

	/**
	 * Expand an abbreviated hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($project, $abbrevHash)
	{
		if (!$project)
			return $abbrevHash;

		if (!(preg_match('/[0-9A-Fa-f]{4,39}/', $abbrevHash))) {
			return $abbrevHash;
		}

		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%H';
		$args[] = $abbrevHash;

		$fullData = explode("\n", $this->exe->Execute($project->GetPath(), GIT_REV_LIST, $args));
		if (empty($fullData[0])) {
			return $abbrevHash;
		}
		if (substr_compare(trim($fullData[0]), 'commit', 0, 6) !== 0) {
			return $abbrevHash;
		}

		if (empty($fullData[1])) {
			return $abbrevHash;
		}

		return trim($fullData[1]);
	}

	/**
	 * Abbreviate a hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($project, $hash)
	{
		if (!$project)
			return $hash;

		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			return $hash;
		}

		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%h';
		$args[] = $hash;

		$abbrevData = explode("\n", $this->exe->Execute($project->GetPath(), GIT_REV_LIST, $args));
		if (empty($abbrevData[0])) {
			return $hash;
		}
		if (substr_compare(trim($abbrevData[0]), 'commit', 0, 6) !== 0) {
			return $hash;
		}

		if (empty($abbrevData[1])) {
			return $hash;
		}

		return trim($abbrevData[1]);
	}
}
