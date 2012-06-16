<?php
/**
 * GitPHP CommitSearch
 *
 * Class to represent a commit log search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'RevList.class.php');

/**
 * CommitSearchType class
 *
 * Enumerates commit search types
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_CommitSearchType
{
	/**
	 * Commit
	 *
	 * Commit search type
	 *
	 * @const
	 */
	const Commit = 1;

	/**
	 * Author
	 *
	 * Author search type
	 *
	 * @const
	 */
	const Author = 2;

	/**
	 * Committer
	 *
	 * Committer search type
	 *
	 * @const
	 */
	const Committer = 3;
}

/**
 * CommitSearch class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_CommitSearch extends GitPHP_RevList
{
	/**
	 * type
	 *
	 * Stores the search type
	 *
	 * @access protected
	 */
	protected $type;

	/**
	 * search
	 *
	 * Stores the search query
	 *
	 * @access protected
	 */
	protected $search;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 * @param int $type search type
	 * @param string $search search string
	 * @param mixed $head head to walk back from
	 * @param int $limit limit of revisions to walk
	 * @param int $skip number of revisions to skip
	 */
	public function __construct($project, $type, $search, $head = null, $limit = 50, $skip = 0)
	{
		parent::__construct($project, $head, $limit, $skip);

		if (!$type) {
			throw new Exception('Search type is required');
		}

		if (empty($search)) {
			throw new Exception('Search string is required');
		}

		$this->type = $type;
		$this->search = $search;
	}

	/**
	 * GetType
	 *
	 * Gets the search type
	 *
	 * @access public
	 * @return int search type
	 */
	public function GetType()
	{
		return $this->type;
	}

	/**
	 * SetType
	 *
	 * Sets the search type
	 *
	 * @access public
	 * @param int $type search type
	 */
	public function SetType($type)
	{
		if (!$type)
			return;

		if ($type == $this->type)
			return;

		if ($this->dataLoaded)
			$this->Clear();

		$this->type = $type;
	}

	/**
	 * GetSearch
	 *
	 * Gets the search query
	 *
	 * @access public
	 * @return string search query
	 */
	public function GetSearch()
	{
		return $this->search;
	}

	/**
	 * SetSearch
	 *
	 * Sets the search query
	 *
	 * @access public
	 * @param string $search search query
	 */
	public function SetSearch($search)
	{
		if (empty($search))
			return;

		if ($this->search == $search)
			return;

		if ($this->dataLoaded)
			$this->Clear();

		$this->search = $search;
	}

	/**
	 * LoadData
	 *
	 * Loads the data for this search
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		$args = array();

		if (GitPHP_GitExe::GetInstance()->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';

		switch ($this->type) {
			case GitPHP_CommitSearchType::Commit:
				$args[] = '--grep="' . addslashes($this->search) . '"';
				break;
			case GitPHP_CommitSearchType::Author:
				$args[] = '--author="' . addslashes($this->search) . '"';
				break;
			case GitPHP_CommitSearchType::Committer:
				$args[] = '--committer="' . addslashes($this->search) . '"';
				break;
		}

		$this->revList = $this->RevList($args);
	}

}
