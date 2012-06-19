<?php
/**
 * GitPHP FileMimeTypeStrategy
 *
 * Interface for file mime reader strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileMimeTypeStrategy interface
 *
 * @package GitPHP
 * @subpackage Git
 */
interface GitPHP_FileMimeTypeStrategy_Interface
{
	/**
	 * GetMime
	 *
	 * Gets the mime type for a blob
	 *
	 * @access public
	 * @param mixed $blob blob
	 * @return string mime type
	 */
	public function GetMime($blob);

	/**
	 * Valid
	 *
	 * Gets whether this mimetype strategy is valid
	 *
	 * @access public
	 * @return bool true if valid
	 */
	public function Valid();
}
