<?php
/**
 * Controller for displaying a project's feed
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @author Christian Weiske <cweiske@cweiske.de>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Feed extends GitPHP_ControllerBase
{
	/**
	 * Number of items to put in feed
	 *
	 * @const
	 */
	const FeedItemCount = 150;

	/**
	 * Rss feed format
	 *
	 * @const
	 */
	const RssFormat = 'rss';

	/**
	 * Atom feed format
	 *
	 * @const
	 */
	const AtomFormat = 'atom';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->preserveWhitespace = true;
		$this->DisableLogging();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->params['format'] == GitPHP_Controller_Feed::RssFormat)
			return 'rss.tpl';
		else if ($this->params['format'] == GitPHP_Controller_Feed::AtomFormat)
			return 'atom.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return '';
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($this->params['format'] == GitPHP_Controller_Feed::RssFormat) {
			if ($local)
				return __('rss');
			else
				return 'rss';
		} else if ($this->params['format'] == GitPHP_Controller_Feed::AtomFormat) {
			if ($local)
				return __('atom');
			else
				return 'atom';
		}
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if ((!isset($this->params['format'])) || empty($this->params['format'])) {
			throw new Exception('A feed format is required');
		}

		if ($this->params['format'] == GitPHP_Controller_Feed::RssFormat) {
			$this->headers[] = "Content-type: text/xml; charset=UTF-8";
		} else if ($this->params['format'] == GitPHP_Controller_Feed::AtomFormat) {
			$this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$compat = $this->GetProject()->GetCompat();
		$strategy = null;
		if ($compat) {
			$strategy = new GitPHP_LogLoad_Git($this->exe);
		} else {
			$strategy = new GitPHP_LogLoad_Raw();
		}
		$log = new GitPHP_Log($this->GetProject(), $this->GetProject()->GetHeadCommit(), $strategy, GitPHP_Controller_Feed::FeedItemCount);
		$log->FilterOldCommits(48*60*60, 20);

		$this->tpl->assign('log', $log);

		$this->tpl->assign('gitexe', $this->exe);
	}

}
