<?php
/**
 * Controller for running a search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Search extends GitPHP_ControllerBase
{

	/**
	 * Commit search type
	 *
	 * @const
	 */
	const CommitSearch = 'commit';

	/**
	 * Author search type
	 *
	 * @const
	 */
	const AuthorSearch = 'author';

	/**
	 * Committer search type
	 *
	 * @const
	 */
	const CommitterSearch = 'committer';

	/**
	 * File search type
	 *
	 * @const
	 */
	const FileSearch = 'file';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if (!$this->config->GetValue('search')) {
			throw new GitPHP_MessageException(__('Search has been disabled'), true);
		}
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->params['searchtype'] == GitPHP_Controller_Search::FileSearch) {
			return 'searchfiles.tpl';
		}
		return 'search.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['searchtype']) ? sha1($this->params['searchtype']) : '') . '|' . (isset($this->params['search']) ? sha1($this->params['search']) : '') . '|' . (isset($this->params['page']) ? $this->params['page'] : 0);
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local && $this->resource) {
			return $this->resource->translate('search');
		}
		return 'search';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (!isset($this->params['searchtype']))
			$this->params['searchtype'] = GitPHP_Controller_Search::CommitSearch;

		if ($this->params['searchtype'] == GitPHP_Controller_Search::FileSearch) {
			if (!$this->config->GetValue('filesearch')) {
				throw new GitPHP_MessageException(__('File search has been disabled'), true);
			}

		}

		if (($this->params['searchtype'] !== GitPHP_Controller_Search::AuthorSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::CommitterSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::CommitSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::FileSearch)) {
			throw new GitPHP_MessageException(__('Invalid search type'), true);
		}

		if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
			throw new GitPHP_MessageException(sprintf(__n('You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2), 2), true);
		}

		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		else
			$this->params['hash'] = 'HEAD';
		if (isset($_GET['pg']))
			$this->params['page'] = $_GET['pg'];
		else
			$this->params['page'] = 0;
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$co = $this->GetProject()->GetCommit($this->params['hash']);

		if (!$co) {
			return;
		}

		$this->tpl->assign('commit', $co);

		$skip = $this->params['page'] * 100;

		$results = null;

		switch ($this->params['searchtype']) {

			case GitPHP_Controller_Search::AuthorSearch:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::AuthorType, $this->params['search'], $this->exe, $co, 101, $skip);
				break;

			case GitPHP_Controller_Search::CommitterSearch:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitterType, $this->params['search'], $this->exe, $co, 101, $skip);
				break;

			case GitPHP_Controller_Search::CommitSearch:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitType, $this->params['search'], $this->exe, $co, 101, $skip);
				break;

			case GitPHP_Controller_Search::FileSearch:
				$results = new GitPHP_FileSearch($this->GetProject(), $co->GetTree(), $this->params['search'], $this->exe, 101, $skip);
				break;
		}

		if ($results->GetCount() > 0) {
			$this->tpl->assign('results', $results);
		}

		if ($results->GetCount() > 100) {
			$this->tpl->assign('hasmore', true);
			$results->SetLimit(100);
		}

		$this->tpl->assign('tree', $co->GetTree());

		$this->tpl->assign('page', $this->params['page']);

	}
	
}
