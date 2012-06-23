<?php
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
	 * Unified diff mode
	 *
	 * @const
	 */
	const UnifiedDiff = 1;

	/**
	 * Side by side diff mode
	 *
	 * @const
	 */
	const SideBySideDiff = 2;

	/**
	 * Diff mode cookie name
	 *
	 * @const
	 */
	const DiffModeCookie = 'GitPHPDiffMode';

	/**
	 * Diff mode cookie lifetime
	 *
	 * @const
	 */
	const DiffModeCookieLifetime = 31536000;			// 1 year
	
	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (!isset($this->params['plain']) || $this->params['plain'] != true) {

			if ($this->DiffMode(isset($_GET['o']) ? $_GET['o'] : '') == GitPHP_Controller_DiffBase::SideBySideDiff) {
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
		$mode = GitPHP_Controller_DiffBase::UnifiedDiff;	// default

		/*
		 * Check cookie
		 */
		if (!empty($_COOKIE[GitPHP_Controller_DiffBase::DiffModeCookie])) {
			$mode = $_COOKIE[GitPHP_Controller_DiffBase::DiffModeCookie];
		} else {
			/*
			 * Create cookie to prevent browser delay
			 */
			setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, $mode, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime);
		}

		if (!empty($overrideMode)) {
			/*
			 * User is choosing a new mode
			 */
			if ($overrideMode == 'sidebyside') {
				$mode = GitPHP_Controller_DiffBase::SideBySideDiff;
				setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, GitPHP_Controller_DiffBase::SideBySideDiff, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime);
			} else if ($overrideMode == 'unified') {
				$mode = GitPHP_Controller_DiffBase::UnifiedDiff;
				setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, GitPHP_Controller_DiffBase::UnifiedDiff, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime);
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
			$this->DisableLogging();
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
		}
	}

}
