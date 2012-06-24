<?php
/**
 * Interface for commit load data strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Commit
 */
interface GitPHP_CommitLoadStrategy_Interface
{
	/**
	 * Gets the data for a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return array commit data
	 */
	public function Load($commit);

	/**
	 * Gets the containing tag for a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return string containing tag
	 */
	public function LoadContainingTag($commit);

	/**
	 * Whether this load strategy loads the abbreviated hash
	 *
	 * @return boolean
	 */
	public function LoadsAbbreviatedHash();
}
