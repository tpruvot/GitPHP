<?php
/**
 * Class representing a list of tags
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TagList extends GitPHP_RefList
{
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
			$tags[] = $this->project->GetTag($tag, $hash);
		}

		return $tags;
	}

	/**
	 * Load tag data
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if ($this->compat)
			$this->refs = $this->ReadRefListGit('tags');
		else
			$this->refs = $this->ReadRefListRaw('tags');
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

		return $this->project->GetTag($tag, $this->refs[$tag]);
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
		if (!$this->dataLoaded)
			$this->LoadData();

		if ($this->compat) {
			$ordered = $this->GetOrderedRefsGit('tags', $order, $count, $skip);
			$tags = array();
			foreach ($ordered as $tag) {
				if (isset($this->refs[$tag])) {
					$tags[] = $this->project->GetTag($tag, $this->refs[$tag]);
				}
			}
			return $tags;
		} else {
			return $this->GetOrderedTagsRaw($order, $count, $skip);
		}
	}

	/**
	 * Get tags in a specific order
	 *
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @param int $skip skip a number of results
	 * @return GitPHP_Tag[] array of tags
	 */
	private function GetOrderedTagsRaw($order, $count = 0, $skip = 0)
	{
		$tags = array();
		foreach ($this->refs as $tag => $hash) {
			$tags[] = $this->project->GetTag($tag, $hash);
		}

		/* TODO add different orders */
		if ($order == '-creatordate') {
			usort($tags, array('GitPHP_Tag', 'CompareCreationEpoch'));
		}

		if (($count > 0) && (count($tags) > $count)) {
			$tags = array_slice($tags, $skip, $count);
		}
		return $tags;
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

		return $this->project->GetTag(key($this->refs), current($this->refs));
	}

}
