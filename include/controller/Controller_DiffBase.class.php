<?php

defined('GITPHP_DIFF_MODE_COOKIE_LIFETIME') || define('GITPHP_DIFF_MODE_COOKIE_LIFETIME', 60*60*24*365);

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
	 * Constants for diff modes
	 */
	const DIFF_UNIFIED = 1;
	const DIFF_SIDEBYSIDE = 2;

	/**
	 * Xiphux style constants
	 */
	const UnifiedDiff = 1;
	const SideBySideDiff = 2;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (!$this->Plain()) {

			if ($this->DiffMode(isset($this->params['diffmode']) ? $this->params['diffmode'] : '') == self::DIFF_SIDEBYSIDE) {
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
		$mode = self::DIFF_UNIFIED;	// default

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
				$mode = self::DIFF_SIDEBYSIDE;
				setcookie(self::DiffModeCookie, self::DIFF_SIDEBYSIDE, time()+self::DiffModeCookieLifetime, $baseurl);
			} else if ($overrideMode == 'unified') {
				$mode = self::DIFF_UNIFIED;
				setcookie(self::DiffModeCookie, self::DIFF_UNIFIED, time()+self::DiffModeCookieLifetime, $baseurl);
			}
		}

		return $mode;
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if ($this->Plain()) {
			$this->DisableLogging();

			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
		} else {
			parent::LoadHeaders();
		}
	}

	/**
	 * Tests if this is a plaintext diff
	 *
	 * @return boolean true if plaintext
	 */
	protected function Plain()
	{
		if (isset($this->params['output']) && ($this->params['output'] == 'plain'))
			return true;

		return false;
	}

}
