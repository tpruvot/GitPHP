<?php
/**
 * Bzip2 archive creation class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Archive_Bzip2 implements GitPHP_ArchiveStrategy_Interface
{
	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Process handle
	 *
	 * @var resource
	 */
	protected $handle;

	/**
	 * Compression level
	 *
	 * @var integer
	 */
	protected $compressLevel;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitExe $exe executable
	 * @param integer $compressLevel compression level
	 */
	public function __construct($exe, $compressLevel = null)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;

		if (!(($compressLevel === null) || (is_int($compressLevel) && ($compressLevel >= 1) && ($compressLevel <= 9))))
			throw new Exception('Invalid compression level');

		$this->compressLevel = $compressLevel;
	}

	/**
	 * Open a descriptor for this archive
	 *
	 * @param GitPHP_Archive $archive archive
	 * @return boolean true on success
	 */
	public function Open($archive)
	{
		if (!$archive)
			return false;

		if ($this->handle) {
			return true;
		}

		$args = array();
		$args[] = '--format=tar';
		$args[] = '--prefix=' . $archive->GetPrefix();
		$args[] = $archive->GetObject()->GetHash();

		$this->handle = $this->exe->Open($archive->GetProject()->GetPath(), GIT_ARCHIVE, $args);

		return ($this->handle !== false);
	}

	/**
	 * Read a chunk of the archive data
	 *
	 * @param int $size size of data to read
	 * @return string|boolean archive data or false
	 */
	public function Read($size = 1048576)
	{
		if (!$this->handle)
			return false;

		if (feof($this->handle))
			return false;

		$data = fread($this->handle, $size);

		if ($this->compressLevel)
			return bzcompress($data, $this->compressLevel);
		else
			return bzcompress($data);
	}

	/**
	 * Close archive descriptor
	 */
	public function Close()
	{
		if (!$this->handle)
			return true;

		pclose($this->handle);

		$this->handle = null;

		return true;
	}

	/**
	 * Gets the file extension for this format
	 *
	 * @return string extension
	 */
	public function Extension()
	{
		return 'tar.bz2';
	}

	/**
	 * Gets the mime type for this format
	 *
	 * @return string mime type
	 */
	public function MimeType()
	{
		return 'application/x-bzip2';
	}

	/**
	 * Gets whether this archiver is valid
	 *
	 * @return boolean true if valid
	 */
	public function Valid()
	{
		return function_exists('bzcompress');
	}
}
