<?php
/**
 * Constant for the number of items to load into the feed
 */
define('GITPHP_FEED_ITEMS', 150);

/**
 * Constant for rss feed format
 */
define('GITPHP_FEED_FORMAT_RSS', 'rss');

/**
 * Constant for atom feed format
 */
define('GITPHP_FEED_FORMAT_ATOM', 'atom');

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
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->preserveWhitespace = true;
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->params['format'] == GITPHP_FEED_FORMAT_RSS)
			return 'rss.tpl';
		else if ($this->params['format'] == GITPHP_FEED_FORMAT_ATOM)
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
		if ($this->params['format'] == GITPHP_FEED_FORMAT_RSS) {
			if ($local)
				return __('rss');
			else
				return 'rss';
		} else if ($this->params['format'] == GITPHP_FEED_FORMAT_ATOM) {
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
		GitPHP_DebugLog::GetInstance()->SetEnabled(false);
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if ((!isset($this->params['format'])) || empty($this->params['format'])) {
			throw new Exception('A feed format is required');
		}

		if ($this->params['format'] == GITPHP_FEED_FORMAT_RSS) {
			$this->headers[] = "Content-type: text/xml; charset=UTF-8";
		} else if ($this->params['format'] == GITPHP_FEED_FORMAT_ATOM) {
			$this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$log = new GitPHP_Log($this->GetProject(), $this->GetProject()->GetHeadCommit(), GITPHP_FEED_ITEMS);
		$log->FilterOldCommits(48*60*60, 20);

		$this->tpl->assign('log', $log);
	}

}
