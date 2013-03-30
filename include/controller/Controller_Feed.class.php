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
	 * Constant for the number of items to load into the feed
	 */
	const FEED_ITEMS = 150;

	/**
	 * Constants for the different feed formats
	 */
	const FEED_FORMAT_RSS = 'rss';
	const FEED_FORMAT_ATOM = 'atom';

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();
		$this->preserveWhitespace = true;
	}

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
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
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return '';
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @access public
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
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
		GitPHP_Log::GetInstance()->SetEnabled(false);
	}

	/**
	 * LoadHeaders
	 *
	 * Loads headers for this template
	 *
	 * @access protected
	 */
	protected function LoadHeaders()
	{
		if ((!isset($this->params['format'])) || empty($this->params['format'])) {
			throw new Exception('A feed format is required');
		}

		if ($this->params['format'] == self::FEED_FORMAT_RSS) {
			$this->headers[] = "Content-type: text/xml; charset=UTF-8";
		} else if ($this->params['format'] == self::FEED_FORMAT_ATOM) {
			$this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
		}
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$log = $this->GetProject()->GetLog('HEAD', self::FEED_ITEMS);

		$entries = count($log);

		if ($entries > 20) {
			/*
			 * Don't show commits older than 48 hours,
			 * but show a minimum of 20 entries
			 */
			for ($i = 20; $i < $entries; ++$i) {
				if ((time() - $log[$i]->GetCommitterEpoch()) > 48*60*60) {
					$log = array_slice($log, 0, $i);
					break;
				}
			}
		}

		$this->tpl->assign('log', $log);
	}

}
