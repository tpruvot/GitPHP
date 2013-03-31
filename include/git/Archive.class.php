<?php
/**
 * Represents an archive (snapshot)
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

class GitPHP_Archive
{
	/**
	 * Compression formats
	 */
	const COMPRESS_TAR = 'tar';
	const COMPRESS_BZ2 = 'bz2';
	const COMPRESS_GZ  = 'gz';
	const COMPRESS_ZIP = 'zip';

	/**
	 * Stores the object type for this archive
	 */
	protected $objectType;

	/**
	 * Stores the object hash for this archive
	 */
	protected $objectHash;

	/**
	 * Stores the project for this archive internally
	 */
	protected $project;

	/**
	 * Stores the archive format internally
	 */
	protected $format;

	/**
	 * Stores the archive filename internally
	 */
	protected $fileName = '';

	/**
	 * Stores the archive path internally
	 */
	protected $path = '';

	/**
	 * Stores the archive prefix internally
	 */
	protected $prefix = '';

	/**
	 * Stores the process handle
	 */
	protected $handle = false;

	/**
	 * Stores the temp file name
	 */
	protected $tempfile = '';

	/**
	 * Instantiates object
	 *
	 * @param mixed $gitObject the object
	 * @param integer $format the format for the archive
	 */
	public function __construct($project, $gitObject, $format = GITPHP_FORMAT_ZIP, $path = '', $prefix = '')
	{
		$this->SetProject($project);
		$this->SetObject($gitObject);
		if (!$this->project && $gitObject) {
			$this->project = $gitObject->GetProject()->GetProject();
		}
		$this->SetFormat($format);
		$this->SetPath($path);
		$this->SetPrefix($prefix);
	}

	/**
	 * Gets the archive format
	 *
	 * @return integer archive format
	 */
	public function GetFormat()
	{
		return $this->format;
	}

	/**
	 * Sets the archive format
	 *
	 * @param integer $format archive format
	 */
	public function SetFormat($format)
	{
		if ((($format == self::COMPRESS_BZ2) && (!function_exists('bzcompress'))) ||
		    (($format == self::COMPRESS_GZ) && (!function_exists('gzencode')))) {
		    /*
		     * Trying to set a format but doesn't have the appropriate
		     * compression function, fall back to tar
		     */
		    $format = self::COMPRESS_TAR;
		}

		$this->format = $format;
	}

	/**
	 * Gets the object for this archive
	 *
	 * @return mixed the git object
	 */
	public function GetObject()
	{
		if ($this->objectType == 'commit') {
			return $this->GetProject()->GetCommit($this->objectHash);
		}

		if ($this->objectType = 'tree') {
			return $this->GetProject()->GetTree($this->objectHash);
		}

		return null;
	}

	/**
	 * Sets the object for this archive
	 *
	 * @param mixed $object the git object
	 */
	public function SetObject($object)
	{
		// Archive only works for commits and trees

		if ($object == null) {
			$this->objectHash = '';
			$this->objectType = '';
			return;
		}

		if ($object instanceof GitPHP_Commit) {
			$this->objectType = 'commit';
			$this->objectHash = $object->GetHash();
			return;
		}

		if ($object instanceof GitPHP_Tree) {
			$this->objectType = 'tree';
			$this->objectHash = $object->GetHash();
			return;
		}

		throw new Exception('Invalid source object for archive');
	}

	/**
	 * Gets the project for this archive
	 *
	 * @return mixed the project
	 */
	public function GetProject()
	{
		if ($this->project)
			return GitPHP_ProjectList::GetInstance()->GetProject($this->project);

		return null;
	}

	/**
	 * Sets the project for this archive
	 *
	 * @param mixed $project the project
	 */
	public function SetProject($project)
	{
		if ($project)
			$this->project = $project->GetProject();
		else
			$this->project = null;
	}

	/**
	 * Gets the extension to use for this archive
	 *
	 * @return string extension for the archive
	 */
	public function GetExtension()
	{
		return GitPHP_Archive::FormatToExtension($this->format);
	}

	/**
	 * Gets the filename for this archive
	 *
	 * @return string filename
	 */
	public function GetFilename()
	{
		if (!empty($this->fileName)) {
			return $this->fileName;
		}

		$fname = $this->GetProject()->GetSlug();

		if (!empty($this->path)) {
			$fname .= '-' . GitPHP_Util::MakeSlug($this->path);
		}

		if (!empty($this->objectHash)) {
			$fname .= '-' . $this->GetProject()->AbbreviateHash($this->objectHash);
		}

		$fname .= '.' . $this->GetExtension();

		return $fname;
	}

	/**
	 * Sets the filename for this archive
	 *
	 * @param string $name filename
	 */
	public function SetFilename($name = '')
	{
		$this->fileName = $name;
	}

	/**
	 * Gets the path to restrict this archive to
	 *
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * Sets the path to restrict this archive to
	 *
	 * @param string $path path to restrict
	 */
	public function SetPath($path = '')
	{
		$this->path = $path;
	}

	/**
	 * Gets the directory prefix to use for files in this archive
	 *
	 * @return string prefix
	 */
	public function GetPrefix()
	{
		if (!empty($this->prefix)) {
			return $this->prefix;
		}

		$pfx = $this->GetProject()->GetSlug() . '/';

		if (!empty($this->path))
			$pfx .= $this->path . '/';

		return $pfx;
	}

	/**
	 * Sets the directory prefix to use for files in this archive
	 *
	 * @param string $prefix prefix to use
	 */
	public function SetPrefix($prefix = '')
	{
		if (empty($prefix)) {
			$this->prefix = $prefix;
			return;
		}

		if (substr($prefix, -1) != '/') {
			$prefix .= '/';
		}

		$this->prefix = $prefix;
	}

	/**
	 * Opens a descriptor for reading archive data
	 *
	 * @return boolean true on success
	 */
	public function Open()
	{
		if (!$this->objectHash)
		{
			throw new Exception('Invalid object for archive');
		}

		if ($this->handle) {
			return true;
		}

		$args = array();

		switch ($this->format) {
			case self::COMPRESS_ZIP:
				$args[] = '--format=zip';
				break;
			case self::COMPRESS_TAR:
			case self::COMPRESS_BZ2:
			case self::COMPRESS_GZ:
				$args[] = '--format=tar';
				break;
		}

		$args[] = '--prefix=' . $this->GetPrefix();
		$args[] = $this->objectHash;

		$this->handle = GitPHP_GitExe::GetInstance()->Open($this->GetProject()->GetPath(), GIT_ARCHIVE, $args);

		if ($this->format == self::COMPRESS_GZ) {
			// hack to get around the fact that gzip files
			// can't be compressed on the fly and the php zlib stream
			// doesn't seem to daisy chain with any non-file streams

			$this->tempfile = tempnam(sys_get_temp_dir(), "GitPHP");

			$compress = GitPHP_Config::GetInstance()->GetValue('compresslevel');

			$mode = 'wb';
			if (is_int($compress) && ($compress >= 1) && ($compress <= 9))
				$mode .= $compress;

			$temphandle = gzopen($this->tempfile, $mode);
			if ($temphandle) {
				while (!feof($this->handle)) {
					gzwrite($temphandle, fread($this->handle, 1048576));
				}
				gzclose($temphandle);

				$temphandle = fopen($this->tempfile, 'rb');
			}
			
			if ($this->handle) {
				pclose($this->handle);
			}
			$this->handle = $temphandle;
		}

		return ($this->handle !== false);
	}

	/**
	 * Close the archive data descriptor
	 *
	 * @return boolean true on success
	 */
	public function Close()
	{
		if (!$this->handle) {
			return true;
		}

		if ($this->format == self::COMPRESS_GZ) {
			fclose($this->handle);
			if (!empty($this->tempfile)) {
				unlink($this->tempfile);
				$this->tempfile = '';
			}
		} else {
			pclose($this->handle);
		}

		$this->handle = null;
		
		return true;
	}

	/**
	 * Read a chunk of the archive data
	 *
	 * @param int $size size of data to read
	 * @return string archive data
	 */
	public function Read($size = 1048576)
	{
		if (!$this->handle) {
			return false;
		}

		if (feof($this->handle)) {
			return false;
		}

		$data = fread($this->handle, $size);

		if ($this->format == self::COMPRESS_BZ2) {
			$data = bzcompress($data, GitPHP_Config::GetInstance()->GetValue('compresslevel', 4));
		}

		return $data;
	}

	/**
	 * Gets the extension to use for a particular format
	 *
	 * @param string $format format to get extension for
	 * @return string file extension
	 */
	public static function FormatToExtension($format)
	{
		switch ($format) {
			case self::COMPRESS_TAR:
				return 'tar';
				break;
			case self::COMPRESS_BZ2:
				return 'tar.bz2';
				break;
			case self::COMPRESS_GZ:
				return 'tar.gz';
				break;
			case self::COMPRESS_ZIP:
				return 'zip';
				break;
		}
	}

	/**
	 * Gets the supported formats for the archiver
	 *
	 * @return array array of formats mapped to extensions
	 */
	public static function SupportedFormats()
	{
		$formats = array();

		$formats[self::COMPRESS_TAR] = GitPHP_Archive::FormatToExtension(self::COMPRESS_TAR);
		
		// TODO check for git > 1.4.3 for zip
		$formats[self::COMPRESS_ZIP] = GitPHP_Archive::FormatToExtension(self::COMPRESS_ZIP);

		if (function_exists('bzcompress'))
			$formats[self::COMPRESS_BZ2] = GitPHP_Archive::FormatToExtension(self::COMPRESS_BZ2);

		if (function_exists('gzencode'))
			$formats[self::COMPRESS_GZ] = GitPHP_Archive::FormatToExtension(self::COMPRESS_GZ);

		return $formats;
	}

}
