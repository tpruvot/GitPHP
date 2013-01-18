<?php
/**
 * Base blob load strategoy
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2013 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
abstract class GitPHP_BlobLoad_Base implements GitPHP_BlobLoadStrategy_Interface
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
	 * Load blob size using git
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return int blob size
	 */
	protected function LoadSize($blob)
	{
		if (!$blob)
			return;

		$args = array();
		$args[] = '-s';
		$args[] = $blob->GetHash();

		return $this->exe->Execute($blob->GetProject()->GetPath(), GIT_CAT_FILE, $args);
	}
}
