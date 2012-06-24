<?php
/**
 * Interface for tag data load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tag
 */
interface GitPHP_TagLoadStrategy_Interface
{
	/**
	 * Gets the data for a tag
	 *
	 * @param GitPHP_Tag $tag tag
	 * @return array array of tag data
	 */
	public function Load($tag);
}
