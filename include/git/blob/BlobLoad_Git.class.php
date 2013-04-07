<?php
/**
 * Blob load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
class GitPHP_BlobLoad_Git extends GitPHP_BlobLoad_Base
{
	/**
	 * Gets the data for a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return string blob data
	 */
	public function Load($blob)
	{
		if (!$blob)
			return;

		$args = array();
		$args[] = 'blob';
		$args[] = $blob->GetHash();

		return $this->exe->Execute($blob->GetProject()->GetPath(), GIT_CAT_FILE, $args);
	}

	/**
	 * Gets the size of a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return int blob size
	 */
	public function Size($blob)
	{
		return $this->LoadSize($blob);
	}
}
