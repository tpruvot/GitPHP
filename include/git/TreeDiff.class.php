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
	 * Stores the from hash
	 */
	protected $fromHash;

	/**
	 * Stores the to hash
	 */
	protected $toHash;

	/**
	 * Stores whether to detect renames
	 */
	protected $renames;

	/**
	 * Stores the project
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Stores the individual file diffs
	 */
	protected $fileDiffs = array();

	/**
	 * Stores whether data has been read
	 */
	protected $dataRead = false;

	/**
	 * Used to preview changes size
	 * array("file" => array(added, deleted));
	 */
	protected $fileStat = array();

	/**
	 * Store total of modified lines
	 */
	protected $totalStat = -1;

	/**
	 * Show trailing spaces changes (diff -w)
	 */
	protected $whiteSpaces = true;

	/**
	 * Path filter for the Diff
	 * @var string
	 */
	protected $path = '';

	/**
	 * Executable
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
	 * @param string $path Path filter to diff two revisions
	 * @param boolean $renames whether to detect file renames
	 * @param boolean $spaces false to ignore white spaces (todo: merge to a single $options param)
	 */
	public function __construct($project, $exe, $toHash, $fromHash = '', $path = '', $renames = false, $spaces = true)
	{
		if (!$project || is_string($project))
			throw new Exception('Project is required');

		$this->project = $project;

		if (!$exe)
			$exe = GitPHP_GitExe::GetInstance();

		$this->exe = $exe;

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

		$this->path = $path;

		$this->renames = $renames;

		$this->whiteSpaces = $spaces;
	}

	/**
	 * Gets the project
	 *
	 * @return mixed project
	 */
	public function GetProject()
	{
		return GitPHP_ProjectList::GetInstance()->GetProject($this->project);
	}

	/**
	 * Reads the tree diff data
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

		if (!$this->whiteSpaces)
			$args[] = '-w';

		if (empty($this->fromHash))
			$args[] = '--root';
		else
			$args[] = $this->fromHash;

		$args[] = $this->toHash;

		if (!empty($this->path)) {
			$args[] = '--';
			$args[] = escapeshellarg($this->path);
		}

		$diffTreeLines = explode("\n", $this->exe->Execute($this->GetProject()->GetPath(), GIT_DIFF_TREE, $args));
		foreach ($diffTreeLines as $line) {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
				try {
					// $this->fileDiffs[] = $this->GetProject()->GetObjectManager()->GetFileDiff($trimmed);

					// TODO: add this in GitPHP_FileDiff
					$fileDiff = new GitPHP_FileDiff($this->GetProject(), $trimmed);
					$file = $fileDiff->GetFromFile();
					$mimetype = GitPHP_Mime::FileMime($file, true);
					$fileDiff->isPicture = ($mimetype == 'image');
					$fileDiff->whiteSpaces = $this->whiteSpaces;
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
	 * Reads the tree diff --numstat
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

		if (!$this->whiteSpaces)
			$args[] = '-w';

		if (empty($this->fromHash))
			$args[] = '--root';
		else
			$args[] = $this->fromHash;

		$args[] = $this->toHash;

		if (!empty($this->path)) {
			$args[] = '--';
			$args[] = escapeshellarg($this->path);
		}

		//Sample output : (added, deleted, file)
		//14      0       css/gitweb.css
		//0       5       doc/AUTHORS
		//0       124     gitphp.css

		$output = $this->exe->Execute($this->GetProject()->GetPath(), GIT_DIFF_TREE, $args);
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
	 * Gets the path filter
	 *
	 * @return string
	 */
	public function GetPath()
	{
		return $this->path;
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
	 */
	function rewind()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return reset($this->fileDiffs);
	}

	/**
	 * Returns the current element in the array
	 */
	function current()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return current($this->fileDiffs);
	}

	/**
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs);
	}

	/**
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return next($this->fileDiffs);
	}

	/**
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs) !== null;
	}

	/**
	 * Gets the number of changed files in this treediff
	 *
	 * @return integer count of changed files
	 */
	public function Count()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->fileDiffs);
	}

	/**
	 * Gets the number of line changes in this treediff
	 *
	 * @return integer count of line changes
	 */
	public function StatCount()
	{
		if ($this->totalStat == -1)
			$this->GetStats();

		return $this->totalStat;
	}
}
