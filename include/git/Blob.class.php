<?php
/**
 * GitPHP Blob
 *
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Blob extends GitPHP_FilesystemObject
{

	/**
	 * data
	 *
	 * Stores the file data
	 *
	 * @access protected
	 */
	protected $data;

	/**
	 * dataRead
	 *
	 * Stores whether data has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * size
	 *
	 * Stores the size
	 *
	 * @access protected
	 */
	protected $size = null;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed blob object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetData
	 *
	 * Gets the blob data
	 *
	 * @access public
	 * @param boolean $explode true to explode data into an array of lines
	 * @return string blob data
	 */
	public function GetData($explode = false)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($explode)
			return explode("\n", $this->data);
		else
			return $this->data;
	}

	/**
	 * ReadData
	 *
	 * Reads the blob data
	 *
	 * @access private
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		if ($this->compat) {
			$args = array();
			$args[] = 'blob';
			$args[] = $this->hash;

			$this->data = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_CAT_FILE, $args);
		} else {
			$this->data = $this->GetProject()->GetObjectLoader()->GetObject($this->hash);
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * FileType
	 *
	 * Gets a file type from its octal mode
	 *
	 * @access public
	 * @static
	 * @param string $octMode octal mode
	 * @param boolean $local true if caller wants localized type
	 * @return string file type
	 */
	public static function FileType($octMode, $local = false)
	{
		$mode = octdec($octMode);
		if (($mode & 0x4000) == 0x4000) {
			if ($local) {
				return __('directory');
			} else {
				return 'directory';
			}
		} else if (($mode & 0xA000) == 0xA000) {
			if ($local) {
				return __('symlink');
			} else {
				return 'symlink';
			}
		} else if (($mode & 0x8000) == 0x8000) {
			if ($local) {
				return __('file');
			} else {
				return 'file';
			}
		}

		if ($local) {
			return __('unknown');
		} else {
			return 'unknown';
		}
	}

	/**
	 * GetSize
	 *
	 * Gets the blob size
	 *
	 * @access public
	 * @return integer size
	 */
	public function GetSize()
	{
		if ($this->size !== null) {
			return $this->size;
		}

		if (!$this->dataRead)
			$this->ReadData();

		return strlen($this->data);
	}

	/**
	 * SetSize
	 *
	 * Sets the blob size
	 *
	 * @access public
	 * @param integer $size size
	 */
	public function SetSize($size)
	{
		$this->size = $size;
	}

	/**
	 * IsBinary
	 *
	 * Tests if this blob is a binary file
	 *
	 * @access public
	 * @return boolean true if binary file
	 */
	public function IsBinary()
	{
		if (!$this->dataRead)
			$this->ReadData();

		$data = $this->data;
		if (strlen($this->data) > 8000)
			$data = substr($data, 0, 8000);

		return strpos($data, chr(0)) !== false;
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('data', 'dataRead');

		return array_merge($properties, parent::__sleep());
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key to use for this object
	 *
	 * @access public
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Blob::CacheKey($this->project->GetProject(), $this->hash);
	}

	/**
	 * CacheKey
	 *
	 * Generates a blob cache key
	 *
	 * @access public
	 * @static
	 * @param string $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		return 'project|' . $proj . '|blob|' . $hash;
	}

}
