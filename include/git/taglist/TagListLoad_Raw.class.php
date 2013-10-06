<?php
/**
 * Tag list load strategy using raw objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\TagList
 */
class GitPHP_TagListLoad_Raw extends GitPHP_RefListLoad_Raw implements GitPHP_TagListLoadStrategy_Interface
{
	/**
	 * Loads the tag list
	 *
	 * @param GitPHP_TagList $tagList tag list
	 * @return array array of tag hashes
	 */
	public function Load($tagList)
	{
		return array($this->GetRefs($tagList, 'tags'), array());
	}

	/**
	 * Loads sorted tags
	 *
	 * @param GitPHP_TagList $tagList tag list
	 * @param string $order list order
	 * @param integer $count number to load
	 * @param integer $skip number to skip
	 */
	public function LoadOrdered($tagList, $order, $count = 0, $skip = 0)
	{
		if (!$tagList)
			return;

		if (empty($order))
			return;

		$tags = $tagList->GetTags();

		/* TODO add different orders */
		if ($order == '-creatordate') {
			@usort($tags, array('GitPHP_Tag', 'CompareCreationEpoch'));
		}

		if ((($count > 0) && (count($tags) > $count)) || ($skip > 0)) {
			if ($count > 0)
				$tags = array_slice($tags, $skip, $count);
			else
				$tags = array_slice($tags, $skip);
		}

		return $tags;
	}
}
