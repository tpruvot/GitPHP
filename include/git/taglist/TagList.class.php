<?php
/**
 * Class representing a list of tags
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\TagList
 */
class GitPHP_TagList extends GitPHP_RefList
{
	/**
	 * Data load strategy
	 *
	 * @var GitPHP_TagListLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Dereferenced tag commits
	 *
	 * @var array
	 */
	protected $commits = array();

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_TagListLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($project, GitPHP_TagListLoadStrategy_Interface $strategy)
	{
		parent::__construct($project);

		if (!$strategy)
			throw new Exception('Tag list load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_TagListLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_TagListLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the tags
	 *
	 * @return GitPHP_Tag[] array of tags
	 */
	public function GetTags()
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		$tags = array();

		foreach ($this->refs as $tag => $hash) {
			$tagObj = $this->project->GetObjectManager()->GetTag($tag, $hash);
			if (isset($this->commits[$tag]))
				$tagObj->SetCommitHash($this->commits[$tag]);
			$tags[] = $tagObj;
		}

		return $tags;
	}

	/**
	 * Get tags pointing to a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return GitPHP_Tag[] array of tags
	 */
	public function GetCommitTags($commit)
	{
		if (!$commit)
			return array();

		$commitHash = $commit->GetHash();

		if (!$this->dataLoaded)
			$this->LoadData();

		if (!isset($this->invertedRefs[$commitHash])) return array();
		$tagNames = $this->invertedRefs[$commitHash];
		$tags = array();
		foreach ($tagNames as $tag) {
			if (isset($this->commits[$tag])) {
				if ($this->commits[$tag] == $commitHash) {
					$tagObj = $this->project->GetObjectManager()->GetTag($tag, $commitHash);
					$tagObj->SetCommitHash($this->commits[$tag]);
					$tags[] = $tagObj;
				}
			} else {
				$tagObj = $this->project->GetObjectManager()->GetTag($tag, $commitHash);
				$tagCommitHash = $tagObj->GetCommitHash();
				if (!empty($tagCommitHash)) {
					$this->commits[$tag] = $tagCommitHash;
				}
				if ($tagCommitHash == $commitHash) {
					$tags[] = $tagObj;
				}
			}
		}
		return $tags;
	}

	/**
	 * Load tag data
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		list($this->refs, $this->commits) = $this->strategy->Load($this);
		foreach ($this->refs as $ref => $hash) $this->invertedRefs[$hash][] = $ref;
	}

	/**
	 * Gets a tag
	 *
	 * @param string $tag tag
	 * @return GitPHP_Tag tag object
	 */
	public function GetTag($tag)
	{
		if (empty($tag))
			return null;

		if (!$this->dataLoaded)
			$this->LoadData();

		if (!isset($this->refs[$tag]))
			return;

		$tagObj = $this->project->GetObjectManager()->GetTag($tag, $this->refs[$tag]);
		if (isset($this->commits[$tag]))
			$tagObj->SetCommitHash($this->commits[$tag]);
		return $tagObj;
	}

	/**
	 * Given a hash, gets the associated tag name
	 *
	 * @param string $hash hash
	 * @return string tag name
	 */
	public function GetTagNameFromHash($hash)
	{
		if (empty($hash))
			return null;

		if (!$this->dataLoaded)
			$this->LoadData();

		$tag = array_search($hash, $this->refs);

		if ($tag === false)
			return null;

		return $tag;
	}

	/**
	 * Gets tags in a specific order
	 *
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @param int $skip skip a number of results
	 * @return GitPHP_Tag[] array of tags
	 */
	public function GetOrderedTags($order, $count = 0, $skip = 0)
	{
		return $this->strategy->LoadOrdered($this, $order, $count, $skip);
	}

	/**
	 * Returns the current revision (overrides base)
	 *
	 * @return GitPHP_Tag
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		$tag = key($this->refs);
		$tagObj = $this->project->GetObjectManager()->GetTag($tag, current($this->refs));
		if (isset($this->commits[$tag])) {
			$tagObj->SetCommitHash($this->commits[$tag]);
		}
		return $tagObj;
	}

}
