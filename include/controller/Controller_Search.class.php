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
	 * @var string
	 */
	const CommitSearch = 'commit';

	/**
	 * Author search type
	 *
	 * @var string
	 */
	const AuthorSearch = 'author';

	/**
	 * Committer search type
	 *
	 * @var string
	 */
	const CommitterSearch = 'committer';

	/**
	 * File search type
	 *
	 * @var string
	 */
	const FileSearch = 'file';

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (!$this->config->GetValue('search')) {
			throw new GitPHP_SearchDisabledException();
		}

		if (empty($this->params['hash']))
			$this->params['hash'] = 'HEAD';
		if (empty($this->params['page']))
			$this->params['page'] = 0;

		if (!isset($this->params['searchtype']))
			$this->params['searchtype'] = GitPHP_Controller_Search::CommitSearch;

		if ($this->params['searchtype'] == GitPHP_Controller_Search::FileSearch) {
			if (!$this->config->GetValue('filesearch')) {
				throw new GitPHP_SearchDisabledException(true);
			}

		}

		if (($this->params['searchtype'] !== GitPHP_Controller_Search::AuthorSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::CommitterSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::CommitSearch) && ($this->params['searchtype'] !== GitPHP_Controller_Search::FileSearch)) {
			throw new GitPHP_InvalidSearchTypeException();
		}

		if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
			throw new GitPHP_SearchLengthException(2);
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
