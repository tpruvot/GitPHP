<?php
/**
 * Controller for displaying graph selection
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Graph extends GitPHP_ControllerBase
{
	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (!$this->config->GetValue('graphs')) {
			throw new Exception('Graphing has been disabled');
		}

		if (empty($this->params['graphtype']))
			$this->params['graphtype'] = 'commitactivity';
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'graph.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return isset($this->params['graphtype']) ? $this->params['graphtype'] : '';
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local && $this->resource) {
			return $this->resource->translate('graph');
		}
		return 'graph';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign('head', $head);
		if (!$head)
			$this->tpl->assign('enablesearch', false);

		if (!empty($this->params['graphtype']))
			$this->tpl->assign('graphtype', $this->params['graphtype']);
	}
}
