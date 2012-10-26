<?php
/**
 * Controller to display a project's feed
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
	 * Constant for the number of items to load into the feed
	 */
	const MAX_FEED_ITEMS = 150;

	/**
	 * Constants for the different feed formats
	 */
	const RssFormat = 'rss';
	const AtomFormat = 'atom';

	const FEED_FORMAT_RSS = 'rss';
	const FEED_FORMAT_ATOM = 'atom';

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();
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
		if ($this->params['format'] == self::FEED_FORMAT_RSS)
			return 'rss.tpl';
		else if ($this->params['format'] == self::FEED_FORMAT_ATOM)
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
		if ($this->params['format'] == self::FEED_FORMAT_RSS) {
			if ($local && $this->resource)
				return $this->resource->translate('rss');
			else
				return 'rss';
		} else if ($this->params['format'] == self::FEED_FORMAT_ATOM) {
			if ($local && $this->resource)
				return $this->resource->translate('atom');
			else
				return 'atom';
		}
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if ((!isset($this->params['format'])) || empty($this->params['format'])) {
			throw new Exception('A feed format is required');
		}

		if ($this->params['format'] == self::FEED_FORMAT_RSS) {
			$this->headers[] = "Content-type: application/rss+xml; charset=UTF-8";
		} else if ($this->params['format'] == self::FEED_FORMAT_ATOM) {
			$this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$log = new GitPHP_GitLog($this->GetProject(), $this->GetProject()->GetHeadCommit(), self::MAX_FEED_ITEMS);
		if ($this->config->HasKey('feedfilter')) {
			$log->FilterCommits($this->config->GetValue('feedfilter'));
		}
		// Don't show commits older than 48 hours, but show a minimum of 20 entries
		$log->FilterOldCommits(48*60*60, 20);

		$this->tpl->assign('log', $log);

		$this->tpl->assign('gitexe', $this->exe);
	}

}
