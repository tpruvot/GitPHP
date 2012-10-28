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
	public function Initialize()
	{
		try {
			$this->InitializeConfig();
		} catch (Exception $e) {
		}

		$this->InitializeResource();

		$this->InitializeUserList();

		$this->InitializeGitExe(false);

		try {
			$this->InitializeProjectList();
		} catch (Exception $e) {
		}

		try {
			$this->InitializeSmarty();
		} catch (Exception $e) {
		}

		if (!empty($this->params['project']) && $this->projectList) {
			$project = $this->projectList->GetProject($this->params['project']);
			if ($project) {
				if ($this->userList && ($this->userList->GetCount() > 0)) {
					if ($project->UserCanAccess((!empty($_SESSION['gitphpuser']) ? $_SESSION['gitphpuser'] : null))) {
						$this->project = $project->GetProject();
					}
				} else {
					$this->project = $project->GetProject();
				}
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
		$this->tpl->assign('message', $this->ExceptionToMessage($this->params['exception']));
		if (($this->params['exception'] instanceof GitPHP_MessageException) && ($this->params['exception']->Error)) {
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

		if ($exception instanceof GitPHP_InvalidHashException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Invalid hash %1$s'), $exception->Hash);
			return sprintf('Invalid hash %1$s', $exception->Hash);
		}

		if ($exception instanceof GitPHP_InvalidGitExecutableException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'), $exception->Executable, 'gitbin');
			return sprintf('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.', $exception->Executable, 'gitbin');
		}

		if ($exception instanceof GitPHP_MissingProjectrootException) {
			if ($this->resource)
				return $this->resource->translate('A projectroot must be set in the config');
			return 'A projectroot must be set in the config';
		}

		if ($exception instanceof GitPHP_MissingMemcacheException) {
			if ($this->resource)
				return $this->resource->translate('The Memcached or Memcache PHP extension is required for Memcache support');
			return 'The Memcached or Memcache PHP extension is required for Memcache support';
		}

		if (($exception instanceof GitPHP_InvalidDirectoryException) || ($exception instanceof GitPHP_InvalidDirectoryConfigurationException)) {
			if ($this->resource)
				return sprintf($this->resource->translate('%1$s is not a directory'), $exception->Directory);
			return sprintf('%1$s is not a directory', $exception->Directory);
		}

		if ($exception instanceof GitPHP_InvalidFileException) {
			if ($this->resource)
				return sprintf($this->resource->translate('%1$s is not a file'), $exception->File);
			return sprintf('%1$s is not a file', $exception->File);
		}

		if ($exception instanceof GitPHP_InvalidGitRepositoryException) {
			if ($this->resource)
				return sprintf($this->resource->translate('%1$s is not a git repository'), $exception->Repository);
			return sprintf('%1$s is not a git repository', $exception->Repository);
		}

		if ($exception instanceof GitPHP_ProjectListFileReadException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Failed to open project list file %1$s'), $exception->File);
			return sprintf('Failed to open project list file %1$s', $exception->File);
		}

		if ($exception instanceof GitPHP_DirectoryTraversalException) {
			if ($this->resource)
				return sprintf($this->resource->translate('%1$s is attempting directory traversal'), $exception->Path);
			return sprintf('%1$s is attempting directory traversal', $exception->Path);
		}

		if ($exception instanceof GitPHP_ProjectrootBoundException) {
			if ($this->resource)
				return sprintf($this->resource->translate('%1$s is outside of the projectroot'), $exception->Path);
			return sprintf('%1$s is outside of the projectroot', $exception->Path);
		}

		if ($exception instanceof GitPHP_InvalidConfigFileException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Could not load config file %1$s'), $exception->File);
			return sprintf('Could not load config file %1$s', $exception->File);
		}

		if ($exception instanceof GitPHP_AmbiguousHashException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Ambiguous abbreviated hash %1$s'), $exception->Hash);
			
			return sprintf('Ambiguous abbreviated hash %1$s', $exception->Hash);
		}

		if ($exception instanceof GitPHP_DirectoryNotFoundException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Directory %1$s not found'), $exception->Directory);

			return sprintf('Directory %1$s not found', $exception->Directory);
		}

		if ($exception instanceof GitPHP_FileNotFoundException) {
			if ($this->resource)
				return sprintf($this->resource->translate('File %1$s not found'), $exception->File);

			return sprintf('File %1$s not found', $exception->File);
		}

		if ($exception instanceof GitPHP_UnauthorizedProjectException) {
			if ($this->resource)
				return sprintf($this->resource->translate('You are not authorized to access project %1$s'), $exception->Project);

			return sprintf('You are not authorized to access project %1$s', $exception->Project);
		}

		if ($exception instanceof GitPHP_DisabledFunctionException) {
			if ($this->resource)
				return sprintf($this->resource->translate('Required function %1$s has been disabled'), $exception->Function);

			return sprintf('Required function %1$s has been disabled', $exception->Function);
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
