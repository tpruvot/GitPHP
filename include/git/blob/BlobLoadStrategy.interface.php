<?php
/**
 * Interface for blob data load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
interface GitPHP_BlobLoadStrategy_Interface
{
	/**
	 * Gets the data for a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return string blob data
	 */
	public function Load($blob);

	/**
	 * Gets the size of a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return int blob size
	 */
	public function Size($blob);
}
