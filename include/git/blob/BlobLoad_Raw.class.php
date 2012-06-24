<?php
/**
 * Blob load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
class GitPHP_BlobLoad_Raw implements GitPHP_BlobLoadStrategy_Interface
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
	 */
	public function __construct($objectLoader)
	{
		if (!$objectLoader)
			throw new Exception('Git object loader is required');

		$this->objectLoader = $objectLoader;
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
}
