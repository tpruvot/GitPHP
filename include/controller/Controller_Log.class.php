<?php
/**
 * Controller for displaying a log
 *
 * @author Christopher Han
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Log extends GitPHP_ControllerBase
{

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (empty($this->params['hash']))
			$this->params['hash'] = 'HEAD';

		if (empty($this->params['page']))
			$this->params['page'] = 0;
	}

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if (isset($this->params['short']) && ($this->params['short'] === true)) {
			return 'shortlog.tpl';
		}
		return 'log.tpl';
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return $this->params['hash'] . '|' . $this->params['page'] . '|' . (isset($this->params['mark']) ? $this->params['mark'] : '');
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @access public
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if (isset($this->params['short']) && ($this->params['short'] === true)) {
			if ($local && $this->resource) {
				return $this->resource->translate('shortlog');
			}
			return 'shortlog';
		}
		if ($local && $this->resource) {
			return $this->resource->translate('log');
		}
		return 'log';
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		if ($this->GetProject()->isAndroidRepo) {
			$this->tpl->assign('branch', $this->GetProject()->repoBranch);
		}
		$this->tpl->assign('commit', $this->GetProject()->GetCommit($this->params['hash']));
		$this->tpl->assign('head', $this->GetProject()->GetHeadCommit());
		$this->tpl->assign('page',$this->params['page']);

		$revlist = $this->GetProject()->GetLog($this->params['hash'], 101, ($this->params['page'] * 100));
		if ($revlist) {
			if (count($revlist) > 100) {
				$this->tpl->assign('hasmorerevs', true);
				$revlist = array_slice($revlist, 0, 100);
			}
			$this->tpl->assign('revlist', $revlist);
		}

		if (isset($this->params['mark'])) {
			$this->tpl->assign('mark', $this->GetProject()->GetCommit($this->params['mark']));
		}
	}

}
