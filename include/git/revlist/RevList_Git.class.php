<?php
/**
 * Class for consuming revlist command using git
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\RevList
 */
class GitPHP_RevList_Git
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
	 * @param GitPHP_GitExe $exe executable
	 */
	public function __construct($exe)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;
	}

	/**
	 * Execute rev-list command
	 *
	 * @param GitPHP_Project $project project
	 * @param string $hash hash to look back from
	 * @param int $count number to return (0 for all)
	 * @param int $skip number of items to skip
	 * @param array $args extra arguments
	 */
	public function RevList($project, $hash, $count, $skip = 0, $args = array())
	{
		if (!$project || empty($hash))
			return;

		$canSkip = true;
		
		if ($skip > 0)
			$canSkip = $this->exe->CanSkip();

		$extraargs = array();

		if ($canSkip) {
			if ($count > 0) {
				$extraargs[] = '--max-count=' . $count;
			}
			if ($skip > 0) {
				$extraargs[] = '--skip=' . $skip;
			}
		} else {
			if ($count > 0) {
				$extraargs[] = '--max-count=' . ($count + $skip);
			}
		}

		$extraargs[] = $hash;

		if (count($args) > 0) {
			$endarg = array_search('--', $args);
			if ($endarg !== false) {
				array_splice($args, $endarg, 0, $extraargs);
			} else {
				$args = array_merge($args, $extraargs);
			}
		} else {
			$args = $extraargs;
		}

		$revlist = explode("\n", $this->exe->Execute($project->GetPath(), GIT_REV_LIST, $args));

		if (!$revlist[count($revlist)-1]) {
			/* the last newline creates a null entry */
			array_splice($revlist, -1, 1);
		}

		if (($skip > 0) && (!$canSkip)) {
			if ($count > 0) {
				return array_slice($revlist, $skip, $count);
			} else {
				return array_slice($revlist, $skip);
			}
		}

		return $revlist;
	}
}
