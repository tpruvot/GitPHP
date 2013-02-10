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
	 * @var int
	 */
	const FeedItemCount = 100;

	/**
	 * Rss feed format
	 *
	 * @var string
	 */
	const RssFormat = 'rss';

	/**
	 * Atom feed format
	 *
	 * @var string
	 */
	const AtomFormat = 'atom';

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
			if ($local && $this->resource)
				return $this->resource->translate('rss');
			else
				return 'rss';
		} else if ($this->params['format'] == GitPHP_Controller_Feed::AtomFormat) {
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

		if ($this->params['format'] == GitPHP_Controller_Feed::RssFormat) {
			$this->headers[] = "Content-type: application/rss+xml; charset=UTF-8";
		} else if ($this->params['format'] == GitPHP_Controller_Feed::AtomFormat) {
			$this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		//$compat = $this->GetProject()->GetCompat();
		$strategy = null;
		//if ($compat) {
			$strategy = new GitPHP_LogLoad_Git($this->exe);
		//} else {
		//	$strategy = new GitPHP_LogLoad_Raw();
		//}
		$log = new GitPHP_Log($this->GetProject(), $this->GetProject()->GetHeadCommit(), $strategy, GitPHP_Controller_Feed::FeedItemCount);
		if ($this->config->HasKey('feedfilter')) {
			$log->FilterCommits($this->config->GetValue('feedfilter'));
		}
		$log->FilterOldCommits(48*60*60, 20);

		$this->tpl->assign('log', $log);

		$this->tpl->assign('gitexe', $this->exe);
	}

}
