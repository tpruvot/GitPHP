<?php
/**
 * Base commit load strategy
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Commit
 */
abstract class GitPHP_CommitLoad_Base implements GitPHP_CommitLoadStrategy_Interface
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
	 * Gets the containing tag for a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return string containing tag
	 */
	public function LoadContainingTag($commit)
	{
		if (!$commit)
			return;

		$args = array();
		$args[] = '--tags';
		$args[] = $commit->GetHash();
		$revs = explode("\n", $this->exe->Execute($commit->GetProject()->GetPath(), GIT_NAME_REV, $args));

		foreach ($revs as $revline) {
			if (preg_match('/^([0-9a-fA-F]{40})\s+tags\/(.+)(\^[0-9]+|\~[0-9]+)$/', $revline, $regs)) {
				if ($regs[1] == $commit->GetHash()) {
					return $regs[2];
				}
			}
		}
	}
}
