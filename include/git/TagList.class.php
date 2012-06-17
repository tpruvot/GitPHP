<?php
/**
 * GitPHP TagList
 *
 * Class representing a list of tags
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * TagList
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TagList extends GitPHP_RefList
{
	/**
	 * GetTags
	 *
	 * Gets the tags
	 *
	 * @access public
	 * @return array array of tags
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
	 * LoadData
	 *
	 * Load tag data
	 *
	 * @access protected
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
	 * GetTag
	 *
	 * Gets a tag
	 *
	 * @access public
	 * @param string $tag tag
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
	 * GetOrderedTags
	 *
	 * Gets tags in a specific order
	 *
	 * @access public
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return array array of tags
	 */
	public function GetOrderedTags($order, $count = 0)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		if ($this->compat) {
			$ordered = $this->GetOrderedRefsGit('tags', $order, $count);
			$tags = array();
			foreach ($ordered as $tag) {
				if (isset($this->refs[$tag])) {
					$tags[] = $this->project->GetTag($tag, $this->refs[$tag]);
				}
			}
			return $tags;
		} else {
			return $this->GetOrderedTagsRaw($order, $count);
		}
	}

	/**
	 * GetOrderedTagsRaw
	 *
	 * Get tags in a specific order
	 *
	 * @access public
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return array array of tags
	 */
	private function GetOrderedTagsRaw($order, $count = 0)
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
			$tags = array_slice($tags, 0, $count);
		}
		return $tags;
	}

	/**
	 * current
	 *
	 * Returns the current revision (overrides base)
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return $this->project->GetTag(key($this->refs), current($this->refs));
	}

}
