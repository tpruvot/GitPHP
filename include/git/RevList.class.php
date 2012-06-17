<?php
/**
 * GitPHP RevList
 *
 * Base class for a revision list
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'Pagination.interface.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * RevList class
 *
 * @package GitPHP
 * @subpackage Git
 * @abstract
 */
abstract class GitPHP_RevList implements Iterator, GitPHP_Pagination
{
	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project = null;

	/**
	 * hashList
	 *
	 * Stores the list of revision hashes
	 *
	 * @access protected
	 */
	protected $hashList = array();

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
	 * hash
	 *
	 * Stores the hash to walk back from
	 *
	 * @access protected
	 */
	protected $hash = false;

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
	 * @param mixed $head head to walk back from
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

		if ($head) {
			$this->hash = $head->GetHash();
		} else {
			$this->hash = $this->project->GetHeadCommit()->GetHash();
		}
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

		return count($this->hashList);
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
				$this->hashList = array_slice($this->hashList, 0, $limit);
			} else if ($limit > $this->limit) {
				/* want more data, have to reload */
				$this->Clear();
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
			$this->Clear();
		}

		$this->skip = $skip;
	}

	/**
	 * GetHead
	 *
	 * Gets the head this log will walk from
	 *
	 * @access public
	 * @return mixed head commit
	 */
	public function GetHead()
	{
		return $this->project->GetCommit($this->hash);
	}

	/**
	 * SetHead
	 *
	 * Sets the head this log will walk from
	 *
	 * @access public
	 * @param mixed $head head commit
	 */
	public function SetHead($head)
	{
		if ($head)
			$this->SetHeadHash($head->GetHash());
		else
			$this->SetHeadHash(null);
	}

	/**
	 * SetHeadHash
	 *
	 * Sets the head hash this log will walk from
	 *
	 * @access public
	 * @param string $hash head commit hash
	 */
	public function SetHeadHash($hash)
	{
		if (empty($hash))
			$hash = $this->project->GetHeadCommit()->GetHash();

		if ($hash != $this->hash) {
			$this->Clear();
			$this->hash = $hash;
		}
	}

	/**
	 * LoadData
	 *
	 * Load the data for this revlist
	 *
	 * @access protected
	 * @abstract
	 */
	protected abstract function LoadData();

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

		return reset($this->hashList);
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

		return $this->project->GetCommit(current($this->hashList));
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

		return key($this->hashList);
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

		return next($this->hashList);
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

		return key($this->hashList) !== null;
	}

	/**
	 * Clear
	 *
	 * Clears the loaded data
	 *
	 * @access public
	 */
	public function Clear()
	{
		if (!$this->dataLoaded)
			return;

		$this->hashList = array();
		reset($this->hashList);

		$this->dataLoaded = false;
	}

	/**
	 * RevList
	 *
	 * Common code for using rev-list command
	 *
	 * @access protected
	 * @param array $args args to give to rev-list
	 * @return array array of hashes
	 */
	protected function RevList($args = array())
	{
		if ($this->limit < 1)
			return;

		$canSkip = true;
		
		if ($this->skip > 0)
			$canSkip = GitPHP_GitExe::GetInstance()->CanSkip();

		if ($canSkip) {
			$args[] = '--max-count=' . $this->limit;
			if ($this->skip > 0) {
				$args[] = '--skip=' . $this->skip;
			}
		} else {
			$args[] = '--max-count=' . ($this->limit + $this->skip);
		}

		$args[] = $this->hash;

		$revlist = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_REV_LIST, $args));

		if (!$revlist[count($revlist)-1]) {
			/* the last newline creates a null entry */
			array_splice($revlist, -1, 1);
		}

		if (($this->skip > 0) && (!$canSkip)) {
			return array_slice($revlist, $this->skip, $this->limit);
		}

		return $revlist;
	}

}
