<?php
/**
 * Interface for tree data load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tree
 */
interface GitPHP_TreeLoadStrategy_Interface
{
	/**
	 * Gets the data for a tree
	 *
	 * @param GitPHP_Tree $tree tree
	 * @return array array of tree contents
	 */
	public function Load($tree);

	/**
	 * Gets the hash paths for a tree
	 *
	 * @param GitPHP_Tree $tree tre
	 * @return array array of treepath and hashpath arrays
	 */
	public function LoadHashPaths($tree);
}
