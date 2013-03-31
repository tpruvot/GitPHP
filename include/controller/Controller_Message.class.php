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
	 * Initialize controller
	 */
	public function Initialize()
	{
		try {
			$this->InitializeConfig();
		} catch (Exception $e) {
		}

		$this->InitializeResource();

		$this->InitializeGitExe(false);

		try {
			$this->InitializeProjectList();
		} catch (Exception $e) {
		}

		try {
			$this->InitializeSmarty();
		} catch (Exception $e) {
		}

		if (isset($this->params['project']) && $this->projectList) {
			$project = $this->projectList->GetProject($this->params['project']);
			if ($project) {
				$this->project = $project->GetProject();
			}
		}

		if (empty($this->params['hash']))
			$this->params['hash'] = 'HEAD';
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
		return sha1(serialize($this->params['exception']));
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
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		parent::LoadHeaders();

		if (($this->params['exception'] instanceof GitPHP_MessageException) && ($this->params['exception']->StatusCode)) {
			$partialHeader = $this->StatusCodeHeader($this->params['exception']->StatusCode);
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
		$message = $this->ExceptionToMessage($this->params['exception']);
		$this->tpl->assign('message', $message);
		if (($this->params['exception'] instanceof GitPHP_MessageException) && ($this->params['exception']->Error)) {
			if (empty($message) && isset($this->params['message']) )
				$this->tpl->assign('message', $this->params['message']);
			$this->tpl->assign('error', true);
		}

		if ($this->project) {
			try {
				$co = $this->GetProject()->GetCommit($this->params['hash']);
				if ($co) {
					$this->tpl->assign('commit', $co);
				}
			} catch (Exception $e) {
			}
		}
	}

	/**
	 * Gets the user-displayed message for an exception
	 *
	 * @param Exception $exception exception
	 * @return string message
	 */
	private function ExceptionToMessage($exception)
	{
		if (!$exception)
			return;

		if ($exception instanceof GitPHP_InvalidProjectParameterException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Invalid project %1$s'), $exception->Project);
			return sprintf('Invalid project %1$s', $exception->Project);
		}
		
		if ($exception instanceof GitPHP_MissingProjectParameterException) {
			if ($this->resource)
				return $this->resource->translate('Project is required');
			return 'Project is required';
		}

		if ($exception instanceof GitPHP_SearchDisabledException) {
			if ($exception->FileSearch) {
				if ($this->resource)
					return $this->resource->translate('File search has been disabled');
				return 'File search has been disabled';
			} else {
				if ($this->resource)
					return $this->resource->translate('Search has been disabled');
				return 'Search has been disabled';
			}
		}

		if ($exception instanceof GitPHP_InvalidSearchTypeException) {
			if ($this->resource)
				return $this->resource->translate('Invalid search type');
			return 'Invalid search type';
		}

		if ($exception instanceof GitPHP_SearchLengthException) {
			if ($this->resource)
				return sprintf($this->resource->ngettext('You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', $exception->MinimumLength), $exception->MinimumLength);
			return sprintf($exception->MinimumLength == 1 ? 'You must enter search text of at least %1$d character' : 'You must enter search text of at least %1$d characters', $exception->MinimumLength);
		}

		return $exception->getMessage();
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
			case 403:
				return '403 Forbidden';
			case 404:
				return '404 Not Found';
			case 500:
				return '500 Internal Server Error';
		}
	}

}
