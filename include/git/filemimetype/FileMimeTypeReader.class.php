<?php
/**
 * Class to read the mime of a blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\FileMimeType
 */
class GitPHP_FileMimeTypeReader
{
	/**
	 * The blob
	 *
	 * @var GitPHP_Blob
	 */
	protected $blob;

	/**
	 * The mime type
	 *
	 * @var string
	 */
	protected $mimeType;

	/**
	 * Whether the mime type has been read
	 *
	 * @var boolean
	 */
	protected $mimeTypeRead = false;

	/**
	 * Stores the specific mime type strategy
	 *
	 * @var GitPHP_FileMimeTypeStrategy
	 */
	protected $strategy;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Blob $blob blob
	 * @param GitPHP_FileMimeTypeStrategy force a strategy
	 */
	public function __construct($blob, $strategy = null)
	{
		if (!$blob)
			throw new Exception('Blob is required');

		$this->blob = $blob;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the blob for this mime reader
	 *
	 * @return GitPHP_Blob blob
	 */
	public function GetBlob()
	{
		return $this->blob;
	}

	/**
	 * Gets the mime
	 *
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
	 * Reads the mime type
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
	 * Read the mime type using a specific strategy
	 *
	 * @param GitPHP_FileMimeTypeStrategy $strategy mime strategy
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
