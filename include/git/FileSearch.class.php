<?php
/**
 * Class to represent a file search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileSearch implements Iterator, GitPHP_Pagination_Interface
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Tree hash
	 *
	 * @var string
	 */
	protected $treeHash;

	/**
	 * Tree path
	 *
	 * @var string
	 */
	protected $treePath;

	/**
	 * The list of all results
	 *
	 * @var GitPHP_FileSearchResult[]
	 */
	protected $allResults = array();

	/**
	 * The paginated subset of results
	 *
	 * @var GitPHP_FileSearchResult[]
	 */
	protected $resultList;

	/**
	 * Search string
	 *
	 * @var string
	 */
	protected $search;

	/**
	 * The limit of objects to load
	 *
	 * @var int
	 */
	protected $limit = 50;

	/**
	 * The number of objects to skip
	 *
	 * @var int
	 */
	protected $skip = 0;

	/**
	 * Whether data has been loaded
	 *
	 * @var boolean
	 */
	protected $dataLoaded = false;

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
	 * @param GitPHP_Tree $tree tree to search
	 * @param string $search string to search
	 * @param GitPHP_GitExe $exe git executable
	 * @param int $limit limit of results to return
	 * @param int $skip number of results to skip
	 */
	public function __construct($project, $tree, $search, $exe, $limit = 50, $skip = 0)
	{
		if (!$project) {
			throw new Exception('Project is required');
		}

		if (!$tree) {
			throw new Exception('Tree is required');
		}

		if (empty($search)) {
			throw new Exception('Search is required');
		}

		if (!$exe) {
			throw new Exception('Git exe is required');
		}

		$this->project = $project;

		$this->treeHash = $tree->GetHash();
		$this->treePath = $tree->GetPath();

		$this->search = $search;

		$this->exe = $exe;

		$this->limit = $limit;
		
		$this->skip = $skip;
	}

	/**
	 * Gets the tree for this search
	 *
	 * @return GitPHP_Tree tree
	 */
	public function GetTree()
	{
		$tree = $this->project->GetObjectManager()->GetTree($this->treeHash);
		$tree->SetPath($this->treePath);
		return $tree;
	}

	/**
	 * Gets the search string
	 *
	 * @return string search
	 */
	public function GetSearch()
	{
		return $this->search;
	}

	/**
	 * Sets the search string
	 *
	 * @param string $search search
	 */
	public function SetSearch($search)
	{
		if (empty($search))
			return;

		if ($this->search == $search)
			return;

		if ($this->dataLoaded) {
			$this->Clear();
		}

		$this->search = $search;
	}

	/**
	 * Gets the count of results
	 *
	 * @return int count
	 */
	public function GetCount()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return count($this->resultList);
	}

	/**
	 * Gets the limit
	 *
	 * @return int limit
	 */
	public function GetLimit()
	{
		return $this->limit;
	}

	/**
	 * Sets the limit
	 *
	 * @param int $limit limit
	 */
	public function SetLimit($limit)
	{
		if ($this->limit == $limit)
			return;

		if ($this->dataLoaded) {
			if ($limit < $this->limit) {
				/* want less data, just trim the array */
				$this->resultList = array_slice($this->resultList, 0, $limit);
			} else {
				/* want more data, have to rebuild subset */
				$this->UpdateSubset();
			}
		}

		$this->limit = $limit;
	}

	/**
	 * Gets the skip number
	 *
	 * @return int skip number
	 */
	public function GetSkip()
	{
		return $this->skip;
	}

	/**
	 * Sets the skip number
	 *
	 * @param int $skip skip number
	 */
	public function SetSkip($skip)
	{
		if ($skip == $this->skip)
			return;

		if ($this->dataLoaded) {
			$this->UpdateSubset();
		}

		$this->skip = $skip;
	}

	/**
	 * Rewinds the iterator
	 *
	 * @return GitPHP_FileSearchResult
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->resultList);
	}

	/**
	 * Returns the current revision
	 *
	 * @return GitPHP_FileSearchResult
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->resultList);
	}

	/**
	 * Returns the current key
	 *
	 * @return int
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->resultList);
	}

	/**
	 * Advance the pointer
	 *
	 * @return GitPHP_FileSearchResult|boolean
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->resultList);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->resultList) !== null;
	}

	/**
	 * Clears the results
	 */
	public function Clear()
	{
		if (!$this->dataLoaded)
			return;

		$this->allResults = array();
		$this->resultList = null;

		$this->dataLoaded = false;
	}

	/**
	 * Load search data
	 */
	private function LoadData()
	{
		$this->dataLoaded = true;

		$this->SearchFilenames();

		$this->SearchFileContents();

		ksort($this->allResults);

		$this->UpdateSubset();
	}

	/**
	 * Searches file names for matches
	 */
	private function SearchFilenames()
	{
		$treePaths = $this->GetTree()->GetTreePaths();

		foreach ($treePaths as $path => $hash) {
			if (preg_match('/' . preg_quote($this->search, '/') . '/i', $path)) {
				if (!isset($this->allResults[$path])) {

					$obj = $this->project->GetObjectManager()->GetTree($hash);
					$obj->SetPath($path);

					$this->allResults[$path] = new GitPHP_FileSearchResult($this->project, $obj, $path);
				}
			}
		}

		$blobPaths = $this->GetTree()->GetBlobPaths();

		foreach ($blobPaths as $path => $hash) {
			if (preg_match('/' . preg_quote($this->search, '/') . '/i', $path)) {
				if (!isset($this->allResults[$path])) {

					$obj = $this->project->GetObjectManager()->GetBlob($hash);
					$obj->SetPath($path);
					
					$this->allResults[$path] = new GitPHP_FileSearchResult($this->project, $obj, $path);
				}
			}
		}
	}

	/**
	 * Searches file contents for matches
	 */
	private function SearchFileContents()
	{
		$args = array();
		$args[] = '-I';
		$args[] = '--full-name';
		$args[] = '--ignore-case';
		$args[] = '-n';
		$args[] = '-e';
		$args[] = '"' . addslashes($this->search) . '"';
		$args[] = $this->treeHash;

		$lines = explode("\n", $this->exe->Execute($this->project->GetPath(), GIT_GREP, $args));


		foreach ($lines as $line) {
			if (preg_match('/^[^:]+:([^:]+):([0-9]+):(.+)$/', $line, $regs)) {
				
				if (isset($this->allResults[$regs[1]])) {

					$result = $this->allResults[$regs[1]];
					$matchingLines = $result->GetMatchingLines();
					$matchingLines[(int)($regs[2])] = trim($regs[3], "\n\r\0\x0B");
					$result->SetMatchingLines($matchingLines);

				} else {
				
					$tree = $this->GetTree();
					$hash = $tree->PathToHash($regs[1]);
					if ($hash) {
						$blob = $this->project->GetObjectManager()->GetBlob($hash);
						$blob->SetPath($regs[1]);
						$result = new GitPHP_FileSearchResult($this->project, $blob, $regs[1]);
						$matchingLines = array();
						$matchingLines[(int)($regs[2])] = trim($regs[3], "\n\r\0\x0B");
						$result->SetMatchingLines($matchingLines);
						$this->allResults[$regs[1]] = $result;
					}

				}

			}
		}
	}

	/**
	 * Extracts the subset of results based on size limits
	 */
	private function UpdateSubset()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		$pos = 0;

		$this->resultList = array();

		foreach ($this->allResults as $result) {

			if ($pos++ < $this->skip)
				continue;

			$this->resultList[] = $result;

			if (count($this->resultList) >= $this->limit)
				break;
		}
	}

}
