<?php
/**
 * Tag list load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\TagList
 */
class GitPHP_TagListLoad_Git extends GitPHP_RefListLoad_Git implements GitPHP_TagListLoadStrategy_Interface
{
	/**
	 * Loads the tag list
	 *
	 * @param GitPHP_TagList $tagList tag list
	 * @return array array of tag hashes
	 */
	public function Load($tagList)
	{
		return $this->GetRefs($tagList, 'tags');
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

		$ordered = $this->GetOrderedRefs($tagList, 'tags', $order, $count, $skip);

		if (!$ordered)
			return;

		$tagObjs = array();
		foreach ($ordered as $tag) {
			if ($tagList->Exists($tag)) {
				$tagObjs[] = $tagList->GetTag($tag);
			}
		}

		return $tagObjs;
	}
}
