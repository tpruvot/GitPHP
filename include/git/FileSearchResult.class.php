<?php
/**
 * GitPHP FileSearchResult
 *
 * Class to represent a single result of a file search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileSearchResult
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileSearchResult
{
	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * objectType
	 *
	 * Stores the object type internally
	 *
	 * @access protected
	 */
	protected $objectType;

	/**
	 * objectHash
	 *
	 * Stores the object hash internally
	 *
	 * @access protected
	 */
	protected $objectHash;

	/**
	 * matchingLines
	 *
	 * stores matching lines internally
	 *
	 * @access protected
	 */
	protected $matchingLines;

	/**
	 * path
	 *
	 * Stores the matching path internally
	 *
	 * @access protected
	 */
	protected $path;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param mixed $object matching object
	 * @param string $path matching path
	 */
	public function __construct($project, $object, $path)
	{
		if (!$project) {
			throw new Exception('Project is required');
		}

		$this->project = $project;

		$this->SetObject($object);

		$this->path = $path;
	}

	/**
	 * GetObject
	 *
	 * Gets the matching object
	 *
	 * @access public
	 * @return mixed matching object
	 */
	public function GetObject()
	{
		if ($this->objectType == 'tree') {
			$tree = $this->project->GetTree($this->objectHash);
			$tree->SetPath($this->path);
			return $tree;
		} else if ($this->objectType == 'blob') {
			$blob = $this->project->GetBlob($this->objectHash);
			$blob->SetPath($this->path);
			return $blob;
		}

		return null;
	}

	/**
	 * SetObject
	 *
	 * Sets the matching object
	 *
	 * @access public
	 * @param mixed $object matching object
	 */
	public function SetObject($object)
	{
		if ($object instanceof GitPHP_Tree) {
			$this->objectType = 'tree';
			$this->objectHash = $object->GetHash();
		} else if ($object instanceof GitPHP_Blob) {
			$this->objectType = 'blob';
			$this->objectHash = $object->GetHash();
		} else {
			throw new Exception('Invalid file search result object');
		}
	}

	/**
	 * GetPath
	 *
	 * Gets the matching path
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * SetPath
	 *
	 * Sets the matching path
	 *
	 * @access public
	 * @param string $path path
	 */
	public function SetPath($path)
	{
		$this->path = $path;
	}

	/**
	 * GetMatchingLines
	 *
	 * Gets the list of matching lines
	 *
	 * @access public
	 * @return array array of matching lines
	 */
	public function GetMatchingLines()
	{
		return $this->matchingLines;
	}

	/**
	 * SetMatchingLines
	 *
	 * Sets the list of matching lines
	 *
	 * @access public
	 * @param array $lines matching lines
	 */
	public function SetMatchingLines($lines)
	{
		$this->matchingLines = $lines;
	}

}
