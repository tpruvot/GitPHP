<?php
/**
 * Base class for a revision list
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\RevList
 */
abstract class GitPHP_RevList implements Iterator, GitPHP_Pagination_Interface
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project = null;

	/**
	 * The list of revision hashes
	 *
	 * @var string[]
	 */
	protected $hashList = array();

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
	 * The hash to walk back from
	 *
	 * @var string
	 */
	protected $hash = false;

	/**
	 * Whether data has been loaded
	 *
	 * @var boolean
	 */
	protected $dataLoaded = false;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_Commit $head head to walk back from
	 * @param int $limit limit of revisions to walk
	 * @param int $skip number of revisions to skip
	 */
	public function __construct($project, $head = null, $limit = 50, $skip = 0)
	{
		if (!$project) {
			throw new Exception('Project is required');
		}

		$this->project = $project;
		$this->limit = $limit;
		$this->skip = $skip;

		if (!$head)
			$head = $this->project->GetHeadCommit();

		if ($head) {
			$this->hash = $head->GetHash();
		}
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
	 * Gets the count
	 *
	 * @return int count
	 */
	public function GetCount()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return count($this->hashList);
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
			if (($limit < $this->limit) && ($limit > 0)) {
				/* want less data, just trim the array */
				$this->hashList = array_slice($this->hashList, 0, $limit);
			} else if (($limit > $this->limit) || ($limit < 1)) {
				/* want more data, have to reload */
				$this->Clear();
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
			$this->Clear();
		}

		$this->skip = $skip;
	}

	/**
	 * Gets the head this log will walk from
	 *
	 * @return GitPHP_Commit head commit
	 */
	public function GetHead()
	{
		return $this->project->GetCommit($this->hash);
	}

	/**
	 * Sets the head this log will walk from
	 *
	 * @param GitPHP_Commit $head head commit
	 */
	public function SetHead($head)
	{
		if ($head)
			$this->SetHeadHash($head->GetHash());
		else
			$this->SetHeadHash(null);
	}

	/**
	 * Gets the head hash this log will walk from
	 *
	 * @return string hash
	 */
	public function GetHeadHash()
	{
		return $this->hash;
	}

	/**
	 * Sets the head hash this log will walk from
	 *
	 * @param string $hash head commit hash
	 */
	public function SetHeadHash($hash)
	{
		if (empty($hash)) {
			$head = $this->project->GetHeadCommit();
			if ($head)
				$hash = $head->GetHash();
		}

		if ($hash != $this->hash) {
			$this->Clear();
			$this->hash = $hash;
		}
	}

	/**
	 * Load the data for this revlist
	 */
	protected abstract function LoadData();

	/**
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->hashList);
	}

	/**
	 * Returns the current revision
	 *
	 * @return GitPHP_Commit
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return $this->project->GetCommit(current($this->hashList));
	}

	/**
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->hashList);
	}

	/**
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->hashList);
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

		return key($this->hashList) !== null;
	}

	/**
	 * Clears the loaded data
	 */
	public function Clear()
	{
		if (!$this->dataLoaded)
			return;

		$this->hashList = array();
		reset($this->hashList);

		$this->dataLoaded = false;
	}

}
