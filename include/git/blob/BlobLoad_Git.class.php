<?php
/**
 * Blob load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
class GitPHP_BlobLoad_Git implements GitPHP_BlobLoadStrategy_Interface
{
	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitExe $exe executable
	 */
	public function __construct($exe)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;
	}

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
}
