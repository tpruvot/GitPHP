<?php
/**
 * Blob load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
class GitPHP_BlobLoad_Raw extends GitPHP_BlobLoad_Base
{
	/**
	 * Object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitObjectLoader $objectLoader object loader
	 * @param GitPHP_GitExe $exe git exe
	 */
	public function __construct($objectLoader, $exe)
	{
		if (!$objectLoader)
			throw new Exception('Git object loader is required');

		$this->objectLoader = $objectLoader;

		parent::__construct($exe);
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

		return $this->objectLoader->GetObject($blob->GetHash());
	}

	/**
	 * Gets the size of a blob
	 *
	 * @param GitPHP_Blob $blob blob
	 * @return int blob size
	 */
	public function Size($blob)
	{
		if (!$blob)
			return;

		if ($blob->DataLoaded())
			return strlen($blob->GetData());

		return $this->LoadSize($blob);
	}
}
