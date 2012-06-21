<?php
/**
 * Constant for unified diff mode
 */
define('GITPHP_DIFF_UNIFIED', 1);

/**
 * Constant for side by side diff mode
 */
define('GITPHP_DIFF_SIDEBYSIDE', 2);

/**
 * Constant of the diff mode cookie in the user's browser
 */
define('GITPHP_DIFF_MODE_COOKIE', 'GitPHPDiffMode');

/**
 * Diff mode cookie lifetime
 */
define('GITPHP_DIFF_MODE_COOKIE_LIFETIME', 60*60*24*365);           // 1 year

/**
 * Base controller for diff-type views
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
abstract class GitPHP_Controller_DiffBase extends GitPHP_ControllerBase
{
	
	/**
	 * Read query into parameters
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
	 * Determines the diff mode to use
	 *
	 * @param string $overrideMode mode overridden by the user
	 */
	protected function DiffMode($overrideMode = '')
	{
		$mode = GITPHP_DIFF_UNIFIED;	// default

		/*
		 * Check cookie
		 */
		if (!empty($_COOKIE[GITPHP_DIFF_MODE_COOKIE])) {
			$mode = $_COOKIE[GITPHP_DIFF_MODE_COOKIE];
		} else {
			/*
			 * Create cookie to prevent browser delay
			 */
			setcookie(GITPHP_DIFF_MODE_COOKIE, $mode, time()+GITPHP_DIFF_MODE_COOKIE_LIFETIME);
		}

		if (!empty($overrideMode)) {
			/*
			 * User is choosing a new mode
			 */
			if ($overrideMode == 'sidebyside') {
				$mode = GITPHP_DIFF_SIDEBYSIDE;
				setcookie(GITPHP_DIFF_MODE_COOKIE, GITPHP_DIFF_SIDEBYSIDE, time()+GITPHP_DIFF_MODE_COOKIE_LIFETIME);
			} else if ($overrideMode == 'unified') {
				$mode = GITPHP_DIFF_UNIFIED;
				setcookie(GITPHP_DIFF_MODE_COOKIE, GITPHP_DIFF_UNIFIED, time()+GITPHP_DIFF_MODE_COOKIE_LIFETIME);
			}
		}

		return $mode;
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			GitPHP_DebugLog::GetInstance()->SetEnabled(false);
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
		}
	}

}
