<?php
/**
 * Tree load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tree
 */
class GitPHP_TreeLoad_Raw extends GitPHP_TreeLoad_Base
{
	/**
	 * Object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitObjectLoader $objectLoader object loader
	 * @param GitPHP_GitExe $exe git exe
	 */
	public function __construct($objectLoader, $exe)
	{
		if (!$objectLoader)
			throw new Exception('Git object loader is required');

		$this->objectLoader = $objectLoader;

		parent::__construct($exe);
	}

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

		$treeData = $this->objectLoader->GetObject($tree->GetHash());

		$start = 0;
		$len = strlen($treeData);
		while ($start < $len) {
			$pos = strpos($treeData, "\0", $start);

			list($mode, $path) = explode(' ', substr($treeData, $start, $pos-$start), 2);
			$mode = str_pad($mode, 6, '0', STR_PAD_LEFT);
			$hash = bin2hex(substr($treeData, $pos+1, 20));
			$start = $pos + 21;

			$octmode = octdec($mode);

			if ($octmode == 57344) {
				// submodules not currently supported
				continue;
			}

			if (!empty($treePath))
				$path = $treePath . '/' . $path;

			$data = array();
			$data['hash'] = $hash;
			if ($octmode & 0x4000) {
				// tree
				$data['type'] = 'tree';
			} else {
				// blob
				$data['type'] = 'blob';
			}

			$data['mode'] = $mode;
			$data['path'] = $path;

			$contents[] = $data;
		}

		return $contents;
	}
}
