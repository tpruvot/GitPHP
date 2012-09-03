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
	 * @var int
	 */
	const UnifiedDiff = 1;

	/**
	 * Side by side diff mode
	 *
	 * @var int
	 */
	const SideBySideDiff = 2;

	/**
	 * Diff mode cookie name
	 *
	 * @var string
	 */
	const DiffModeCookie = 'GitPHPDiffMode';

	/**
	 * Diff mode cookie lifetime
	 *
	 * @var int
	 */
	const DiffModeCookieLifetime = 31536000;			// 1 year
	
	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (!$this->Plain()) {

			if ($this->DiffMode(isset($this->params['diffmode']) ? $this->params['diffmode'] : '') == GitPHP_Controller_DiffBase::SideBySideDiff) {
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

		$baseurl = GitPHP_Util::BaseUrl();

		/*
		 * Check cookie
		 */
		if (!empty($_COOKIE[GitPHP_Controller_DiffBase::DiffModeCookie])) {
			$mode = $_COOKIE[GitPHP_Controller_DiffBase::DiffModeCookie];
		} else {
			/*
			 * Create cookie to prevent browser delay
			 */
			setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, $mode, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime, $baseurl);
		}

		if (!empty($overrideMode)) {
			/*
			 * User is choosing a new mode
			 */
			if ($overrideMode == 'sidebyside') {
				$mode = GitPHP_Controller_DiffBase::SideBySideDiff;
				setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, GitPHP_Controller_DiffBase::SideBySideDiff, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime, $baseurl);
			} else if ($overrideMode == 'unified') {
				$mode = GitPHP_Controller_DiffBase::UnifiedDiff;
				setcookie(GitPHP_Controller_DiffBase::DiffModeCookie, GitPHP_Controller_DiffBase::UnifiedDiff, time()+GitPHP_Controller_DiffBase::DiffModeCookieLifetime, $baseurl);
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
