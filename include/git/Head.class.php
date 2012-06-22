<?php
/**
 * Represents a single head
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Head extends GitPHP_Ref
{

	/**
	 * Instantiates head
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $head head name
	 * @param string $headHash head hash
	 */
	public function __construct($project, $head, $headHash = '')
	{
		parent::__construct($project, 'heads', $head, $headHash);
	}

	/**
	 * Gets the commit for this head
	 *
	 * @return GitPHP_Commit commit object for this head
	 */
	public function GetCommit()
	{
		return $this->GetProject()->GetCommit($this->GetHash());
	}
	
	/**
	 * Compares two heads by age
	 *
	 * @param GitPHP_Head $a first head
	 * @param GitPHP_Head $b second head
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$aObj = $a->GetCommit();
		$bObj = $b->GetCommit();
		return GitPHP_Commit::CompareAge($aObj, $bObj);
	}

	/**
	 * Generates a head hash key
	 *
	 * @param string $proj project
	 * @param string $head head name
	 * @return string cache key
	 */
	public static function CacheKey($proj, $head)
	{
		return 'project|' . $proj . '|head|' . $head;
	}

}
