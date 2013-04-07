<?php
/**
 * Interface for collections that can be paginated with a skip and count
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
interface GitPHP_Pagination_Interface
{
	/**
	 * Gets the limit
	 *
	 * @return int limit
	 */
	public function GetLimit();

	/**
	 * Sets the limit
	 *
	 * @param int $limit limit
	 */
	public function SetLimit($limit);

	/**
	 * Gets the skip number
	 *
	 * @return int skip number
	 */
	public function GetSkip();

	/**
	 * Sets the skip number
	 *
	 * @param int $skip skip number
	 */
	public function SetSkip($skip);
}
