<?php
/**
 * GitPHP FileMimeTypeReader
 *
 * Class to read the mime of a blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileMimeTypeReader class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileMimeTypeReader
{
	/**
	 * blob
	 *
	 * Stores the blob internally
	 *
	 * @access protected
	 */
	protected $blob;

	/**
	 * mimeType
	 *
	 * Stores the mimeType internally
	 *
	 * @access protected
	 */
	protected $mimeType;

	/**
	 * mimeTypeRead
	 *
	 * Stores whether the mime type has been read
	 *
	 * @access protected
	 */
	protected $mimeTypeRead = false;

	/**
	 * strategy
	 *
	 * Stores the specific mime type strategy
	 *
	 * @access protected
	 */
	protected $strategy;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $blob blob
	 */
	public function __construct($blob, $strategy = null)
	{
		if (!$blob)
			throw new Exception('Blob is required');

		$this->blob = $blob;

		$this->strategy = $strategy;
	}

	/**
	 * GetBlob
	 *
	 * Gets the blob for this mime reader
	 *
	 * @access public
	 * @return mixed blob
	 */
	public function GetBlob()
	{
		return $this->blob;
	}

	/**
	 * GetMimeType
	 *
	 * Gets the mime
	 *
	 * @access public
	 * @param bool $typeOnly true to get just the main type
	 * @return string type
	 */
	public function GetMimeType($typeOnly = false)
	{
		if (!$this->mimeTypeRead)
			$this->ReadMimeType();

		if (!empty($this->mimeType) && $typeOnly)
			return strtok($this->mimeType, '/');

		return $this->mimeType;
	}

	/**
	 * ReadMimeType
	 *
	 * Reads the mime type
	 *
	 * @access private
	 */
	private function ReadMimeType()
	{
		$this->mimeTypeRead = true;

		if ($this->strategy) {
			/* forced strategy */
			$this->ReadMimeTypeStrategy($this->strategy);
			return;
		}

		if ($this->ReadMimeTypeStrategy(new GitPHP_FileMimeType_Fileinfo()))
			return;

		if ($this->ReadMimeTypeStrategy(new GitPHP_FileMimeType_FileExe()))
			return;

		$this->ReadMimeTypeStrategy(new GitPHP_FileMimeType_Extension());
	}

	/**
	 * ReadMimeTypeStrategy
	 *
	 * Read the mime type using a specific strategy
	 *
	 * @access private
	 * @param mixed $strategy mime strategy
	 * @return bool true if successful
	 */
	public function ReadMimeTypeStrategy($strategy)
	{
		if (!$strategy)
			return false;

		if (!$strategy->Valid())
			return false;

		$this->mimeType = $strategy->GetMime($this->blob);

		return !empty($this->mimeType);
	}
	
}
