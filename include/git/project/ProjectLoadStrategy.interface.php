<?php
/**
 * Interface for project load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Project
 */
interface GitPHP_ProjectLoadStrategy_Interface
{
	/**
	 * Load a project's epoch
	 *
	 * @param GitPHP_Project $project project
	 * @return string epoch
	 */
	public function LoadEpoch($project);

	/**
	 * Load a project's head hash
	 *
	 * @param GitPHP_Project $project
	 * @return string head hash
	 */
	public function LoadHead($project);

	/**
	 * Expand an abbreviated hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($project, $abbrevHash);

	/**
	 * Abbreviate a hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($project, $hash);
}
