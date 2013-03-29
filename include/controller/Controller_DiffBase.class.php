<?php
/**
 * GitPHP Controller DiffBase
 *
 * Base controller for diff-type views
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */


/**
 * Constants for diff modes
 */
defined('GITPHP_DIFF_UNIFIED') ||    define('GITPHP_DIFF_UNIFIED', 1);
defined('GITPHP_DIFF_SIDEBYSIDE') || define('GITPHP_DIFF_SIDEBYSIDE', 2);

defined('GITPHP_DIFF_MODE_COOKIE_LIFETIME') || define('GITPHP_DIFF_MODE_COOKIE_LIFETIME', 60*60*24*365);

/**
 * DiffBase controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
abstract class GitPHP_Controller_DiffBase extends GitPHP_ControllerBase
{
	/**
	 * Diff mode cookie name
	 *
	 * @var string
	 */
	const DiffModeCookie = 'GitPHPDiffMode';

	/**
	 * Diff mode cookie lifetime
	 */
	const DiffModeCookieLifetime = GITPHP_DIFF_MODE_COOKIE_LIFETIME; // 1 year

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
		if (!isset($this->params['plain']) || $this->params['plain'] != true) {

			if ($this->DiffMode(isset($_GET['o']) ? $_GET['o'] : '') == GITPHP_DIFF_SIDEBYSIDE) {
				$this->params['sidebyside'] = true;
			}

		}
	}

	/**
	 * DiffMode
	 *
	 * Determines the diff mode to use
	 *
	 * @param string $overrideMode mode overridden by the user
	 * @access protected
	 */
	protected function DiffMode($overrideMode = '')
	{
		$mode = GITPHP_DIFF_UNIFIED;	// default

		$baseurl = GitPHP_Util::BaseUrl();

		/*
		 * Check cookie
		 */
		if (!empty($_COOKIE[self::DiffModeCookie])) {
			$mode = $_COOKIE[self::DiffModeCookie];
		} else {
			/*
			 * Create cookie to prevent browser delay
			 */
			setcookie(self::DiffModeCookie, $mode, time()+self::DiffModeCookieLifetime, $baseurl);
		}

		if (!empty($overrideMode)) {
			/*
			 * User is choosing a new mode
			 */
			if ($overrideMode == 'sidebyside') {
				$mode = GITPHP_DIFF_SIDEBYSIDE;
				setcookie(self::DiffModeCookie, GITPHP_DIFF_SIDEBYSIDE, time()+self::DiffModeCookieLifetime, $baseurl);
			} else if ($overrideMode == 'unified') {
				$mode = GITPHP_DIFF_UNIFIED;
				setcookie(self::DiffModeCookie, GITPHP_DIFF_UNIFIED, time()+self::DiffModeCookieLifetime, $baseurl);
			}
		}

		return $mode;
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
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			GitPHP_Log::GetInstance()->SetEnabled(false);
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
		}
	}

}
