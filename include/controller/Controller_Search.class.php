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
			throw new GitPHP_MessageException(__('Search has been disabled'), true);
		}

		if (empty($this->params['hash']))
			$this->params['hash'] = 'HEAD';
		if (empty($this->params['page']))
			$this->params['page'] = 0;

		if (!isset($this->params['searchtype']))
			$this->params['searchtype'] = self::SEARCH_COMMIT;

		if ($this->params['searchtype'] == self::SEARCH_FILE) {
			if (!$this->config->GetValue('filesearch')) {
				// throw new GitPHP_SearchDisabledException(true);
				throw new GitPHP_MessageException(__('File search has been disabled'), true);
			}
		}

		if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
			throw new GitPHP_MessageException(sprintf(__n('You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2), 2), true);
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
		$this->tpl->assign('commit', $co);

		$results = array();
		if ($co) {
			switch ($this->params['searchtype']) {

				case self::SEARCH_COMMIT:
					if (preg_match('/^([0-9a-f]{5,40})$/i', $this->params['search'], $regs)) {
						$hash = $this->GetProject()->ExpandHash($this->params['search']);
						$this->params['search'] = $hash;
					} else {
						$hash = $co->GetHash();
					}
					$results = $this->GetProject()->SearchCommit($this->params['search'], $hash, 101, ($this->params['page'] * 100));
					break;

				case self::SEARCH_AUTHOR:
					$results = $this->GetProject()->SearchAuthor($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
					break;

				case self::SEARCH_COMMITTER:
					$results = $this->GetProject()->SearchCommitter($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
					break;
				case self::SEARCH_FILE:
					$results = $co->SearchFiles($this->params['search'], 101, ($this->params['page'] * 100));
					break;
				default:
					throw new GitPHP_MessageException(__('Invalid search type'));

			}
		}

		if (count($results) < 1) {
			throw new GitPHP_MessageException(sprintf(__('No matches for "%1$s"'), $this->params['search']), false);
		}

		if (count($results) > 100) {
			$this->tpl->assign('hasmore', true);
			$results = array_slice($results, 0, 100, true);
		}
		$this->tpl->assign('results', $results);

		$this->tpl->assign('tree', $co->GetTree());

		$this->tpl->assign('page', $this->params['page']);

	}
	
}
