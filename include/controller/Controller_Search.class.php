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
	 * Constants for the various search types
	 */
	const CommitSearch = 'commit';
	const AuthorSearch = 'author';
	const CommiterSearch = 'committer';
	const FileSearch = 'file';

	/**
	 * Search types (standard style)
	 */
	const SEARCH_COMMIT = 'commit';
	const SEARCH_AUTHOR = 'author';
	const SEARCH_COMMITTER = 'committer';
	const SEARCH_FILE = 'file';

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
			$this->params['searchtype'] = self::SEARCH_COMMIT;

		if ($this->params['searchtype'] == self::SEARCH_FILE) {
			if (!$this->config->GetValue('filesearch')) {
				throw new GitPHP_SearchDisabledException(true);
			}
		}

		if (($this->params['searchtype'] !== self::SEARCH_AUTHOR)
		 && ($this->params['searchtype'] !== self::SEARCH_COMMITTER)
		 && ($this->params['searchtype'] !== self::SEARCH_COMMIT)
		 && ($this->params['searchtype'] !== self::SEARCH_FILE))
			throw new GitPHP_InvalidSearchTypeException();

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
		if ($this->params['searchtype'] == self::SEARCH_FILE) {
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

		$results = array();
		$skip = $this->params['page'] * 100;

		$exe = GitPHP_GitExe::GetInstance();

		switch ($this->params['searchtype']) {

			case self::SEARCH_COMMIT:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitType, $this->params['search'], $exe, $co, 101, $skip);
				break;
			case self::SEARCH_AUTHOR:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::AuthorType, $this->params['search'], $exe, $co, 101, $skip);
				break;
			case self::SEARCH_COMMITTER:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitterType, $this->params['search'], $exe, $co, 101, $skip);
				break;
			case self::SEARCH_FILE:
				$results = new GitPHP_FileSearch($this->GetProject(), $co->GetTree(), $this->params['search'], $this->exe, 101, $skip);
				break;
			default:
				throw new GitPHP_MessageException(__('Invalid search type'));
		}

		if ($results->GetCount() < 1) {
			throw new GitPHP_MessageException(sprintf(__('No matches for "%1$s"'), $this->params['search']), false);
		}

		if ($results->GetCount() > 100) {
			$this->tpl->assign('hasmore', true);
			$results->SetLimit(100);
		}

		$this->tpl->assign('results', $results);

		$this->tpl->assign('tree', $co->GetTree());

		$this->tpl->assign('page', $this->params['page']);

	}

}
