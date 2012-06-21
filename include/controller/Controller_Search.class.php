<?php
/**
 * Constant for commit search type
 */
define('GITPHP_SEARCH_COMMIT', 'commit');

/**
 * Constant for author search type
 */
define('GITPHP_SEARCH_AUTHOR', 'author');

/**
 * Constant for committer search type
 */
define('GITPHP_SEARCH_COMMITTER', 'committer');

/**
 * Constant for file search type
 */
define('GITPHP_SEARCH_FILE', 'file');

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
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if (!$this->config->GetValue('search', true)) {
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
		if ($this->params['searchtype'] == GITPHP_SEARCH_FILE) {
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
		if ($local) {
			return __('search');
		}
		return 'search';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (!isset($this->params['searchtype']))
			$this->params['searchtype'] = GITPHP_SEARCH_COMMIT;

		if ($this->params['searchtype'] == GITPHP_SEARCH_FILE) {
			if (!$this->config->GetValue('filesearch', true)) {
				throw new GitPHP_MessageException(__('File search has been disabled'), true);
			}

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

			case GITPHP_SEARCH_AUTHOR:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::AuthorType, $this->params['search'], $co, 101, $skip);
				break;

			case GITPHP_SEARCH_COMMITTER:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitterType, $this->params['search'], $co, 101, $skip);
				break;

			case GITPHP_SEARCH_COMMIT:
				$results = new GitPHP_CommitSearch($this->GetProject(), GitPHP_CommitSearch::CommitType, $this->params['search'], $co, 101, $skip);
				break;

			case GITPHP_SEARCH_FILE:
				$results = new GitPHP_FileSearch($this->GetProject(), $co->GetTree(), $this->params['search'], 101, $skip);
				break;

			default:
				throw new GitPHP_MessageException(__('Invalid search type'));
				break;
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
