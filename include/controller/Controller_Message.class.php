<?php
/**
 * Controller for displaying a message page
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Message extends GitPHP_ControllerBase
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->config = GitPHP_Config::GetInstance();

		$this->InitializeGitExe(false);

		try {
			$this->InitializeProjectList();
		} catch (Exception $e) {
		}

		$this->InitializeSmarty();

		if (isset($_GET['p']) && $this->projectList) {
			$project = $this->projectList->GetProject(str_replace(chr(0), '', $_GET['p']));
			if ($project) {
				$this->project = $project->GetProject();
			}
		}

		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];
		if (isset($_GET['st']))
			$this->params['searchtype'] = $_GET['st'];

		$this->ReadQuery();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->project)
			return 'projectmessage.tpl';
		return 'message.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return sha1($this->params['message']) . '|' . ($this->params['error'] ? '1' : '0');;
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		// This isn't a real controller
		return '';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		else
			$this->params['hash'] = 'HEAD';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if (isset($this->params['statuscode']) && !empty($this->params['statuscode'])) {
			$partialHeader = $this->StatusCodeHeader($this->params['statuscode']);
			if (!empty($partialHeader)) {
				if (substr(php_sapi_name(), 0, 8) == 'cgi-fcgi') {
					/*
					 * FastCGI requires a different header
					 */
					$this->headers[] = 'Status: ' . $partialHeader;
				} else {
					$this->headers[] = 'HTTP/1.1 ' . $partialHeader;
				}
			}
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$this->tpl->assign('message', $this->params['message']);
		if (isset($this->params['error']) && ($this->params['error'])) {
			$this->tpl->assign('error', true);
		}
		if ($this->project) {
			$co = $this->GetProject()->GetCommit($this->params['hash']);
			if ($co) {
				$this->tpl->assign('commit', $co);
			}
		}
	}

	/**
	 * Gets the header for an HTTP status code
	 *
	 * @param integer $code status code
	 * @return string header
	 */
	private function StatusCodeHeader($code)
	{
		switch ($code) {
			case 500:
				return '500 Internal Server Error';
		}
	}

}
