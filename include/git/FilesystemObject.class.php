<?php
/**
 * GitPHP Filesystem Object
 *
 * Base class for all git objects that represent
 * a filesystem item
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Git Filesystem object class
 *
 * @abstract
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_FilesystemObject extends GitPHP_GitObject
{

	/**
	 * path
	 *
	 * Stores the object path
	 *
	 * @access protected
	 */
	protected $path = '';

	/**
	 * mode
	 *
	 * Stores the object mode
	 *
	 * @access protected
	 */
	protected $mode;

	/**
	 * commitHash
	 *
	 * Stores the hash of the commit this object belongs to
	 *
	 * @access protected
	 */
	protected $commitHash;

	/**
	 * pathTree
	 *
	 * Stores the trees of this object's base path
	 *
	 * @access protected
	 */
	protected $pathTree;

	/**
	 * pathTreeRead
	 *
	 * Stores whether the trees of the object's base path have been read
	 *
	 * @access protected
	 */
	protected $pathTreeRead = false;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git filesystem object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetName
	 *
	 * Gets the object name
	 *
	 * @access public
	 * @return string name
	 */
	public function GetName()
	{
		if (!empty($this->path))
			return GitPHP_Util::BaseName($this->path);

		return '';
	}

	/**
	 * GetPath
	 *
	 * Gets the full path
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		if (!empty($this->path))
			return $this->path;

		return '';
	}

	/**
	 * SetPath
	 *
	 * Sets the object path
	 *
	 * @access public
	 * @param string $path object path
	 */
	public function SetPath($path)
	{
		$this->path = $path;
	}

	/**
	 * GetMode
	 *
	 * Gets the object mode
	 *
	 * @access public
	 * @return string mode
	 */
	public function GetMode()
	{
		return $this->mode;
	}

	/**
	 * GetModeString
	 *
	 * Gets the mode as a readable string
	 *
	 * @access public
	 * @return string mode string
	 */
	public function GetModeString()
	{
		if (empty($this->mode))
			return '';

		$mode = octdec($this->mode);

		/*
		 * Git doesn't store full ugo modes,
		 * it only knows if something is a directory,
		 * symlink, or an executable or non-executable file
		 */
		if (($mode & 0x4000) == 0x4000)
			return 'drwxr-xr-x';
		else if (($mode & 0xA000) == 0xA000)
			return 'lrwxrwxrwx';
		else if (($mode & 0x8000) == 0x8000) {
			if (($mode & 0x0040) == 0x0040)
				return '-rwxr-xr-x';
			else
				return '-rw-r--r--';
		}
		return '----------';
	}

	/**
	 * SetMode
	 *
	 * Sets the object mode
	 *
	 * @access public
	 * @param string $mode tree mode
	 */
	public function SetMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * GetCommit
	 *
	 * Gets the commit this object belongs to
	 *
	 * @access public
	 * @return mixed commit object
	 */
	public function GetCommit()
	{
		return $this->GetProject()->GetCommit($this->commitHash);
	}

	/**
	 * SetCommit
	 *
	 * Sets the commit this object belongs to
	 *
	 * @access public
	 * @param mixed $commit commit object
	 */
	public function SetCommit($commit)
	{
		if ($commit)
			$this->SetCommitHash($commit->GetHash());
		else
			$this->SetCommitHash(null);
	}

	/**
	 * SetCommitHash
	 *
	 * Sets the hash of the commit this object belongs to
	 *
	 * @access public
	 * @param string $commitHash commit hash
	 */
	public function SetCommitHash($commitHash)
	{
		$this->commitHash = $commitHash;
	}

	/**
	 * GetPathTree
	 *
	 * Gets the objects of the base path
	 *
	 * @access public
	 * @return array array of tree objects
	 */
	public function GetPathTree()
	{
		if (!$this->pathTreeRead)
			$this->ReadPathTree();

		$pathTree = array();
		for ($i = 0; $i < count($this->pathTree); ++$i) {
			$data = $this->pathTree[$i];

			if (isset($data['hash']) && !empty($data['hash'])) {
				$tree = $this->GetProject()->GetTree($data['hash']);
				$tree->SetPath($data['path']);
				$pathTree[] = $tree;
			}
		}

		return $pathTree;
	}

	/**
	 * ReadPathTree
	 *
	 * Reads the objects of the base path
	 *
	 * @access private
	 */
	private function ReadPathTree()
	{
		$this->pathTreeRead = true;

		if (empty($this->path)) {
			/* this is a top level tree, it has no subpath */
			return;
		}

		$path = $this->path;

		$commit = $this->GetCommit();

		while (($pos = strrpos($path, '/')) !== false) {
			$path = substr($path, 0, $pos);
			$pathhash = $commit->PathToHash($path);
			if (!empty($pathhash)) {
				$data = array();
				$data['hash'] = $pathhash;
				$data['path'] = $path;
				$this->pathTree[] = $data;
			}
		}

		if (count($this->pathTree) > 0) {
			$this->pathTree = array_reverse($this->pathTree);
		}
	}

	/**
	 * ComparePath
	 *
	 * Compares two objects by path
	 *
	 * @access public
	 * @static
	 * @param mixed $a first object
	 * @param mixed $b second object
	 * @return integer comparison result
	 */
	public static function ComparePath($a, $b)
	{
		return strcmp($a->GetPath(), $b->GetPath());
	}

}
