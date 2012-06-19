<?php
/**
 * GitPHP FileSearch
 *
 * Class to represent a file search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * FileSearch class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileSearch implements Iterator, GitPHP_Pagination_Interface
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
	 * treeHash
	 *
	 * Stores the tree hash internally
	 *
	 * @access protected
	 */
	protected $treeHash;

	/**
	 * treePath
	 *
	 * Stores the tree path internally
	 *
	 * @access protected
	 */
	protected $treePath;

	/**
	 * allResults
	 *
	 * Stores the list of results internally
	 *
	 * @access protected
	 */
	protected $allResults = array();

	/**
	 * resultList
	 *
	 * Stores the limited subset of results internally
	 *
	 * @access protected
	 */
	protected $resultList;

	/**
	 * search
	 *
	 * Stores the search string
	 *
	 * @access protected
	 */
	protected $search;

	/**
	 * limit
	 *
	 * Stores the limit of objects to load
	 *
	 * @access protected
	 */
	protected $limit = 50;

	/**
	 * skip
	 *
	 * Stores the number of objects to skip
	 *
	 * @access protected
	 */
	protected $skip = 0;

	/**
	 * dataLoaded
	 *
	 * Stores whether data has been loaded
	 *
	 * @access protected
	 */
	protected $dataLoaded = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 * @param mixed $tree tree to search
	 * @param string $search string to search
	 * @param int $limit limit of results to return
	 * @param int $skip number of results to skip
	 */
	public function __construct($project, $tree, $search, $limit = 50, $skip = 0)
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

		$this->project = $project;

		$this->treeHash = $tree->GetHash();
		$this->treePath = $tree->GetPath();

		$this->search = $search;

		$this->limit = $limit;
		
		$this->skip = $skip;
	}

	/**
	 * GetTree
	 *
	 * Gets the tree for this search
	 *
	 * @access public
	 */
	public function GetTree()
	{
		$tree = $this->project->GetTree($this->treeHash);
		$tree->SetPath($this->treePath);
		return $tree;
	}

	/**
	 * GetSearch
	 *
	 * Gets the search
	 *
	 * @access public
	 * @return string search
	 */
	public function GetSearch()
	{
		return $this->search;
	}

	/**
	 * SetSearch
	 *
	 * Sets the search
	 *
	 * @access public
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
	 * GetCount
	 *
	 * Gets the count
	 *
	 * @access public
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
	 * GetLimit
	 *
	 * Gets the limit
	 *
	 * @access public
	 * @return int limit
	 */
	public function GetLimit()
	{
		return $this->limit;
	}

	/**
	 * SetLimit
	 *
	 * Sets the limit
	 *
	 * @access public
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
	 * GetSkip
	 *
	 * Gets the skip number
	 *
	 * @access public
	 * @return int skip number
	 */
	public function GetSkip()
	{
		return $this->skip;
	}

	/**
	 * SetSkip
	 *
	 * Sets the skip number
	 *
	 * @access public
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
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->resultList);
	}

	/**
	 * current
	 *
	 * Returns the current revision
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->resultList);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->resultList);
	}

	/**
	 * next
	 *
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->resultList);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->resultList) !== null;
	}

	/**
	 * Clear
	 *
	 * Clears the results
	 *
	 * @access public
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
	 * LoadData
	 *
	 * Load search data
	 *
	 * @access private
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
	 * SearchFilenames
	 *
	 * Searches file names for matches
	 *
	 * @access private
	 */
	private function SearchFilenames()
	{
		$treePaths = $this->GetTree()->GetTreePaths();

		foreach ($treePaths as $path => $hash) {
			if (preg_match('/' . preg_quote($this->search, '/') . '/i', $path)) {
				if (!isset($this->allResults[$path])) {

					$obj = $this->project->GetTree($hash);
					$obj->SetPath($path);

					$this->allResults[$path] = new GitPHP_FileSearchResult($this->project, $obj, $path);
				}
			}
		}

		$blobPaths = $this->GetTree()->GetBlobPaths();

		foreach ($blobPaths as $path => $hash) {
			if (preg_match('/' . preg_quote($this->search, '/') . '/i', $path)) {
				if (!isset($this->allResults[$path])) {

					$obj = $this->project->GetBlob($hash);
					$obj->SetPath($path);
					
					$this->allResults[$path] = new GitPHP_FileSearchResult($this->project, $obj, $path);
				}
			}
		}
	}

	/**
	 * SearchFileContents
	 *
	 * Searches file contents for matches
	 *
	 * @access private
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

		$lines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_GREP, $args));


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
						$blob = $this->project->GetBlob($hash);
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
	 * UpdateSubset
	 *
	 * Extracts the subset of results based on size limits
	 *
	 * @access private
	 */
	private function UpdateSubset()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		$pos = 0;
		$count = 0;

		$this->resultList = array();

		foreach ($this->allResults as $path => $result) {

			if ($pos++ < $this->skip)
				continue;

			$this->resultList[] = $result;

			if (count($this->resultList) >= $this->limit)
				break;
		}
	}

}
