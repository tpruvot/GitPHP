<?php
/**
 * Interface for file mime reader strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\FileMimeType
 */
interface GitPHP_FileMimeTypeStrategy_Interface
{
	/**
	 * Gets the mime type for a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return string mime type
	 */
	public function GetMime($blob);

	/**
	 * Gets whether this mimetype strategy is valid
	 *
	 * @return bool true if valid
	 */
	public function Valid();
}
