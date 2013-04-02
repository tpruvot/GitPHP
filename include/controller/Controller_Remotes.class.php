<?php
/**
 * Controller for displaying heads
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Remotes extends GitPHP_ControllerBase
{

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (empty($this->params['page']))
			$this->params['page'] = 0;
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'remotes.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return $this->params['page'];
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
			return $this->resource->translate('remotes');
		}
		return 'remotes';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{

		$this->tpl->assign('page', $this->params['page']);
		$skip = $this->params['page'] * 100;

		$headlist = $this->GetProject()->GetRemoteHeadList()->GetOrderedHeads('-committerdate', 101, $skip);
		if (isset($headlist) && (count($headlist) > 0)) {
			if (count($headlist) > 100) {
				$headlist = array_slice($headlist, 0, 100);
				$this->tpl->assign('hasmoreheads', true);
			}
			$this->tpl->assign("remotelist",$headlist);
		}

		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign("head",$head);
	}

}
