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
	 * Gets the template for this controller
	 *
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
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return $this->params['hash'] . '|' . $this->params['page'] . '|' . (isset($this->params['mark']) ? $this->params['mark'] : '');
	}

	/**
	 * Gets the name of this controller's action
	 *
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
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$commit = $this->GetProject()->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $commit);
		$this->tpl->assign('head', $this->GetProject()->GetHeadCommit());
		$this->tpl->assign('page',$this->params['page']);

		//$compat = $this->GetProject()->GetCompat();
		$skip = $this->params['page'] * 100;
		$strategy = null;
		//if ($compat || ($skip > $this->config->GetValue('largeskip'))) {
			$strategy = new GitPHP_LogLoad_Git($this->exe);
		//} else {
		//	$strategy = new GitPHP_LogLoad_Raw();
		//}
		$revlist = new GitPHP_Log($this->GetProject(), $commit, $strategy, 101, $skip);

		if ($revlist->GetCount() > 100) {
			$this->tpl->assign('hasmorerevs', true);
			$revlist->SetLimit(100);
		}
		$this->tpl->assign('revlist', $revlist);

		if (isset($this->params['mark'])) {
			$this->tpl->assign('mark', $this->GetProject()->GetCommit($this->params['mark']));
		}
	}

}
