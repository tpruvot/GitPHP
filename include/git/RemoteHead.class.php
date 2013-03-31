<?php
/**
 * Represents a single remote head
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_RemoteHead extends GitPHP_Ref
{

	/**
	 * Instantiates head
	 *
	 * @param mixed $project the project
	 * @param string $head head name
	 * @param string $headHash head hash
	 * @throws Exception exception on invalid head or hash
	 */
	public function __construct($project, $head, $headHash = '', $refDir='remotes')
	{
		$head = preg_replace('#^refs/remotes/#', '', $head);
		parent::__construct($project, $refDir, $head, $headHash);
	}

	/*
	 * Get Remote Head Name
	 * 
	 * @return string
	 */
	public function GetRemoteName()
	{
		$ref = $this->GetName();

		// exclude branch name
		$ar = explode('/',$ref);
		array_pop($ar);
		$remote = implode('/',$ar);

		return $remote;
	}

	/**
	 * Gets the commit for this head
	 *
	 * @return mixed commit object for this tag
	 */
	public function GetCommit()
	{
		return $this->GetProject()->GetCommit($this->GetHash());
	}

	/**
	 * Compares two heads by age
	 *
	 * @param mixed $a first head
	 * @param mixed $b second head
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$aObj = $a->GetCommit();
		$bObj = $b->GetCommit();
		if ($aObj == null) return 1;
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
		GitPHP_Log::GetInstance()->Log('RemoteHead::CacheKey(): head='.$head);

		if (is_object($proj))
			return 'project|' . $proj->GetProject . '|head|' . $head;

		return 'project|' . $proj . '|head|' . $head;
	}
}
