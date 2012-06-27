<?php
/**
 * Represents differences between two commit trees
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TreeDiff implements Iterator
{
	
	/**
	 * The from tree hash
	 *
	 * @var string
	 */
	protected $fromHash;

	/**
	 * The to tree hash
	 *
	 * @var string
	 */
	protected $toHash;

	/**
	 * Whether to detect renames
	 *
	 * @var boolean
	 */
	protected $renames;

	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The individual file diffs
	 *
	 * @var GitPHP_FileDiff[]
	 */
	protected $fileDiffs = array();

	/**
	 * Whether data has been read
	 *
	 * @var boolean
	 */
	protected $dataRead = false;

	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_GitExe $exe executable
	 * @param string $toHash to commit hash
	 * @param string $fromHash from commit hash
	 * @param boolean $renames whether to detect file renames
	 */
	public function __construct($project, $exe, $toHash, $fromHash = '', $renames = false)
	{
		if (!$project)
			throw new Exception('Project is required');
		$this->project = $project;

		if (!$exe)
			throw new Exception('Git executable is required');
		$this->exe = $exe;

		$toCommit = $project->GetCommit($toHash);
		$this->toHash = $toHash;

		if (empty($fromHash)) {
			$parent = $toCommit->GetParent();
			if ($parent) {
				$this->fromHash = $parent->GetHash();
			}
		} else {
			$this->fromHash = $fromHash;
		}

		$this->renames = $renames;
	}

	/**
	 * Gets the project
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Reads the tree diff data
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		$this->fileDiffs = array();

		$args = array();

		$args[] = '-r';
		if ($this->renames)
			$args[] = '-M';

		if (empty($this->fromHash))
			$args[] = '--root';
		else
			$args[] = $this->fromHash;

		$args[] = $this->toHash;

		$diffTreeLines = explode("\n", $this->exe->Execute($this->GetProject()->GetPath(), GIT_DIFF_TREE, $args));
		foreach ($diffTreeLines as $line) {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
				try {
					$this->fileDiffs[] = $this->GetProject()->GetObjectManager()->GetFileDiff($trimmed);
				} catch (Exception $e) {
				}
			}
		}
	}

	/**
	 * Gets the from hash for this treediff
	 *
	 * @return string from hash
	 */
	public function GetFromHash()
	{
		return $this->fromHash;
	}

	/**
	 * Gets the to hash for this treediff
	 *
	 * @return string to hash
	 */
	public function GetToHash()
	{
		return $this->toHash;
	}

	/**
	 * Get whether this treediff is set to detect renames
	 *
	 * @return boolean true if renames will be detected
	 */
	public function GetRenames()
	{
		return $this->renames;
	}

	/**
	 * Set whether this treediff is set to detect renames
	 *
	 * @param boolean $renames whether to detect renames
	 */
	public function SetRenames($renames)
	{
		if ($renames == $this->renames)
			return;

		$this->renames = $renames;
		$this->dataRead = false;
	}

	/**
	 * Rewinds the iterator
	 *
	 * @return GitPHP_FileDiff
	 */
	function rewind()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return reset($this->fileDiffs);
	}

	/**
	 * Returns the current element in the array
	 *
	 * @return GitPHP_FileDiff
	 */
	function current()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return current($this->fileDiffs);
	}

	/**
	 * Returns the current key
	 *
	 * @return int
	 */
	function key()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs);
	}

	/**
	 * Advance the pointer
	 *
	 * @return GitPHP_FileDiff
	 */
	function next()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return next($this->fileDiffs);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs) !== null;
	}

	/**
	 * Gets the number of file changes in this treediff
	 *
	 * @return integer count of file changes
	 */
	public function Count()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->fileDiffs);
	}

}
