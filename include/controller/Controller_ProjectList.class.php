<?php
/**
 * Controller for listing projects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_ProjectList extends GitPHP_ControllerBase
{

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->multiProject = true;

		parent::Initialize();

		if ($this->userList && ($this->userList->GetCount() > 0)) {
			if (!$this->config->GetValue('showrestrictedprojects') || (isset($this->params['opml']) && ($this->params['opml'] === true)) || (isset($this->params['txt']) && ($this->params['txt'] === true))) {
				$this->projectList->FilterByUser((!empty($_SESSION['gitphpuser']) ? $_SESSION['gitphpuser'] : null));
			}
		}

		if (empty($this->params['sort']))
			$this->params['sort'] = 'project';
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			return 'opml.tpl';
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			return 'projectindex.tpl';
		}
		return 'projectlist.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		$cachekey = (!empty($_SESSION['gitphpuser']) ? $_SESSION['gitphpuser'] : '');
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			return $cachekey;
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			return $cachekey;
		}
		$cachekey .= '|' . $this->params['sort'] . '|' . (isset($this->params['search']) ? $this->params['search'] : '');
		return $cachekey;
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			if ($local && $this->resource) {
				return $this->resource->translate('opml');
			}
			return 'opml';
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			if ($local && $this->resource) {
				return $this->resource->translate('project index');
			}
			return 'project index';
		}
		if ($local && $this->resource) {
			return $this->resource->translate('projects');
		}
		return 'projects';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			$this->headers[] = "Content-type: text/xml; charset=UTF-8";
			$this->DisableLogging();
			$this->preserveWhitespace = true;
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			$this->headers[] = "Content-type: text/plain; charset=utf-8";
			$this->headers[] = "Content-Disposition: inline; filename=\"index.aux\"";
			$this->DisableLogging();
		} else {
			parent::LoadHeaders();
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$this->tpl->assign('sort', $this->params['sort']);
		
		$this->projectList->Sort($this->params['sort']);

		if ((empty($this->params['opml']) || ($this->params['opml'] !== true)) &&
		    (empty($this->params['txt']) || ($this->params['txt'] !== true)) &&
		    (!empty($this->params['search']))) {
		    	$this->tpl->assign('search', $this->params['search']);
			$matches = $this->projectList->Filter($this->params['search']);
			if (count($matches) > 0) {
				$this->tpl->assign('projectlist', $matches);
			}
		} else {
			if ($this->projectList->Count() > 0)
				$this->tpl->assign('projectlist', $this->projectList);
		}
	}

}
