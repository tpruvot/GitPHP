<?php
/**
 * Tree load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tree
 */
class GitPHP_TreeLoad_Git extends GitPHP_TreeLoad_Base
{
	/**
	 * Gets the data for a tree
	 *
	 * @param GitPHP_Tree $tree tree
	 * @return array array of tree contents
	 */
	public function Load($tree)
	{
		if (!$tree)
			return;

		$contents = array();

		$treePath = $tree->GetPath();

		$args = array();
		$args[] = '--full-name';
		if ($this->exe->CanShowSizeInTree())
			$args[] = '-l';
		$args[] = '-t';
		$args[] = $tree->GetHash();
		
		$lines = explode("\n", $this->exe->Execute($tree->GetProject()->GetPath(), GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})(\s+[0-9]+|\s+-)?\t(.+)$/", $line, $regs)) {
				switch($regs[2]) {
					case 'tree':
						$data = array();
						$data['type'] = 'tree';
						$data['hash'] = $regs[3];
						$data['mode'] = $regs[1];

						$path = $regs[5];
						if (!empty($treePath))
							$path = $treePath . '/' . $path;
						$data['path'] = $path;

						$contents[] = $data;
						break;
					case 'blob':
						$data = array();
						$data['type'] = 'blob';
						$data['hash'] = $regs[3];
						$data['mode'] = $regs[1];

						$path = $regs[5];
						if (!empty($treePath))
							$path = $treePath . '/' . $path;
						$data['path'] = $path;

						$size = trim($regs[4]);
						if (!empty($size))
							$data['size'] = $size;

						$contents[] = $data;
						break;
				}
			}
		}

		return $contents;
	}
}
