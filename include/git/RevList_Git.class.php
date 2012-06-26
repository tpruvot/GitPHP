<?php
/**
 * Class for consuming revlist command using git
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
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
	 * @param int $count number to return
	 * @param int $skip number of items to skip
	 * @param array $args extra arguments
	 */
	public function RevList($project, $hash, $count, $skip = 0, $args = array())
	{
		if (!$project || empty($hash))
			return;

		if ($count < 1)
			return;

		$canSkip = true;
		
		if ($skip > 0)
			$canSkip = $this->exe->CanSkip();

		if ($canSkip) {
			$args[] = '--max-count=' . $count;
			if ($skip > 0) {
				$args[] = '--skip=' . $skip;
			}
		} else {
			$args[] = '--max-count=' . ($count + $skip);
		}

		$args[] = $hash;

		$revlist = explode("\n", $this->exe->Execute($project->GetPath(), GIT_REV_LIST, $args));

		if (!$revlist[count($revlist)-1]) {
			/* the last newline creates a null entry */
			array_splice($revlist, -1, 1);
		}

		if (($skip > 0) && (!$canSkip)) {
			return array_slice($revlist, $skip, $count);
		}

		return $revlist;
	}
}
