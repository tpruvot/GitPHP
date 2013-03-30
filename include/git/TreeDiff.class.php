<?php
/**
 * GitPHP Tree Diff
 *
 * Represents differences between two commit trees
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * TreeDiff class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TreeDiff implements Iterator
{
	
	/**
	 * fromHash
	 *
	 * Stores the from hash
	 *
	 * @access protected
	 */
	protected $fromHash;

	/**
	 * toHash
	 *
	 * Stores the to hash
	 *
	 * @access protected
	 */
	protected $toHash;

	/**
	 * renames
	 *
	 * Stores whether to detect renames
	 *
	 * @access protected
	 */
	protected $renames;

	/**
	 * project
	 *
	 * Stores the project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * fileDiffs
	 *
	 * Stores the individual file diffs
	 *
	 * @access protected
	 */
	protected $fileDiffs = array();

	/**
	 * dataRead
	 *
	 * Stores whether data has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * fileStat
	 *
	 * Used to preview changes size
	 * array("file" => array(added, deleted));
	 *
	 * @access protected
	 */
	protected $fileStat = array();

	/**
	 * $totalStat
	 *
	 * Store total of modified lines
	 *
	 * @access protected
	 */
	protected $totalStat = -1;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 * @param string $toHash to commit hash
	 * @param string $fromHash from commit hash
	 * @param boolean $renames whether to detect file renames
	 * @return mixed TreeDiff object
	 * @throws Exception exception on invalid parameters
	 */
	public function __construct($project, $toHash, $fromHash = '', $renames = false)
	{
		$this->project = $project->GetProject();

		$toCommit = $project->GetCommit($toHash);
		$this->toHash = $toHash;

		if (empty($fromHash) && is_object($toCommit)) {
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
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		return GitPHP_ProjectList::GetInstance()->GetProject($this->project);
	}

	/**
	 * ReadData
	 *
	 * Reads the tree diff data
	 *
	 * @access private
	 */
	private function ReadData()
	{
		if ($this->totalStat == -1)
			$this->GetStats();

		if ($this->totalStat > 2000) {
			//TODO: too big...
		}

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

		$diffTreeLines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_DIFF_TREE, $args));
		foreach ($diffTreeLines as $line) {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
				try {
					$fileDiff = new GitPHP_FileDiff($this->GetProject(), $trimmed);
					$file = $fileDiff->GetFromFile();
					$mimetype = GitPHP_Mime::FileMime($file, true);
					$fileDiff->isPicture = ($mimetype == 'image');
					if (!$fileDiff->isPicture) {
						if (isset($this->fileStat[$file])) {
							$arStat = $this->fileStat[$file];
							$fileDiff->totAdd = reset($arStat);
							$fileDiff->totDel = next($arStat);
						}
					}
					$this->fileDiffs[] = $fileDiff;
				} catch (Exception $e) {
				}
			}
		}
	}

	/**
	 * GetStats
	 *
	 * Reads the tree diff --numstat
	 *
	 * @access private
	 */
	private function GetStats()
	{
		$this->fileStat = array();
		$this->totalStat = 0;

		$args = array();

		$args[] = '--numstat';
		$args[] = '-r';
		if ($this->renames)
			$args[] = '-M';

		if (empty($this->fromHash))
			$args[] = '--root';
		else
			$args[] = $this->fromHash;

		$args[] = $this->toHash;

		//Sample output : (added, deleted, file)
		//14      0       css/gitweb.css
		//0       5       doc/AUTHORS
		//0       124     gitphp.css

		$output = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_DIFF_TREE, $args);
		$re_split = "^(\d+)\s+(\d+)\s+(.*)$";
		if (preg_match_all('/'.$re_split.'/m', $output, $m, PREG_PATTERN_ORDER)) {
			foreach ($m[3] as $key => $file) {
				$add = intval($m[1][$key]);
				$del = intval($m[2][$key]);
				$this->fileStat[$file] = array($add, $del);
				$this->totalStat += $add + $del;
			}
		}
	}

	/**
	 * GetFromHash
	 *
	 * Gets the from hash for this treediff
	 *
	 * @access public
	 * @return string from hash
	 */
	public function GetFromHash()
	{
		return $this->fromHash;
	}

	/**
	 * GetToHash
	 *
	 * Gets the to hash for this treediff
	 *
	 * @access public
	 * @return string to hash
	 */
	public function GetToHash()
	{
		return $this->toHash;
	}

	/**
	 * GetRenames
	 *
	 * Get whether this treediff is set to detect renames
	 *
	 * @access public
	 * @return boolean true if renames will be detected
	 */
	public function GetRenames()
	{
		return $this->renames;
	}

	/**
	 * SetRenames
	 *
	 * Set whether this treediff is set to detect renames
	 *
	 * @access public
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
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return reset($this->fileDiffs);
	}

	/**
	 * current
	 *
	 * Returns the current element in the array
	 */
	function current()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return current($this->fileDiffs);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs);
	}

	/**
	 * next
	 *
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return next($this->fileDiffs);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs) !== null;
	}

	/**
	 * Count
	 *
	 * Gets the number of changed files in this treediff
	 *
	 * @access public
	 * @return integer count of changed files
	 */
	public function Count()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->fileDiffs);
	}

	/**
	 * StatCount
	 *
	 * Gets the number of line changes in this treediff
	 *
	 * @access public
	 * @return integer count of line changes
	 */
	public function StatCount()
	{
		if ($this->totalStat == -1)
			$this->GetStats();

		return $this->totalStat;
	}
}
