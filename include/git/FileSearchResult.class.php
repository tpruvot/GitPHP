<?php
/**
 * Class to represent a single result of a file search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileSearchResult
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The object type
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * The object hash
	 *
	 * @var string
	 */
	protected $objectHash;

	/**
	 * Matching lines from blob
	 *
	 * @var string[]
	 */
	protected $matchingLines;

	/**
	 * The matching path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project the project
	 * @param GitPHP_Tree|GitPHP_Blob $object matching object
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
	 * Gets the matching object
	 *
	 * @return GitPHP_Tree|GitPHP_Blob|null matching object
	 */
	public function GetObject()
	{
		if ($this->objectType == 'tree') {
			$tree = $this->project->GetObjectManager()->GetTree($this->objectHash);
			$tree->SetPath($this->path);
			return $tree;
		} else if ($this->objectType == 'blob') {
			$blob = $this->project->GetObjectManager()->GetBlob($this->objectHash);
			$blob->SetPath($this->path);
			return $blob;
		}

		return null;
	}

	/**
	 * Sets the matching object
	 *
	 * @param GitPHP_Tree|GitPHP_Blob $object matching object
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
	 * Gets the matching path
	 *
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * Sets the matching path
	 *
	 * @param string $path path
	 */
	public function SetPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Gets the list of matching lines
	 *
	 * @return string[] array of matching lines
	 */
	public function GetMatchingLines()
	{
		return $this->matchingLines;
	}

	/**
	 * Sets the list of matching lines
	 *
	 * @param string[] $lines matching lines
	 */
	public function SetMatchingLines($lines)
	{
		$this->matchingLines = $lines;
	}

}
