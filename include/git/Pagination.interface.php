<?php
/**
 * GitPHP Pagination
 *
 * Interface for collections that can be paginated
 * with a skip and count
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Pagination interface
 *
 * @package GitPHP
 * @subpackage Git
 */
interface GitPHP_Pagination
{
	public function GetLimit();
	public function SetLimit($limit);
	public function GetSkip();
	public function SetSkip($skip);
}
