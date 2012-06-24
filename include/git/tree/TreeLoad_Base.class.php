<?php
/**
 * Base tree load strategy
 *
 * @author CHristopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tree
 */
abstract class GitPHP_TreeLoad_Base implements GitPHP_TreeLoadStrategy_Interface
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
	 * Gets the hash paths for a tree
	 *
	 * @param GitPHP_Tree $tree tre
	 * @return array array of treepath and hashpath arrays
	 */
	public function LoadHashPaths($tree)
	{
		if (!$tree)
			return;

		$treePaths = array();
		$blobPaths = array();

		$args = array();
		$args[] = '--full-name';
		$args[] = '-r';
		$args[] = '-t';
		$args[] = $tree->GetHash();

		$lines = explode("\n", $this->exe->Execute($tree->GetProject()->GetPath(), GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/", $line, $regs)) {
				switch ($regs[2]) {
					case 'tree':
						$treePaths[trim($regs[4])] = $regs[3];
						break;
					case 'blob';
						$blobPaths[trim($regs[4])] = $regs[3];
						break;
				}
			}
		}

		return array(
			$treePaths,
			$blobPaths
		);
	}
}
