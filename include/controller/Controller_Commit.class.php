<?php
/**
 * Controller for displaying a commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commit extends GitPHP_ControllerBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if (isset($this->params['jstip']) && $this->params['jstip']) {
			return 'committip.tpl';
		}
		return 'commit.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return $this->params['hash'];
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local) {
			return __('commit');
		}
		return 'commit';
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

		if (isset($_GET['o']) && ($_GET['o'] == 'jstip')) {
			$this->params['jstip'] = true;
			GitPHP_DebugLog::GetInstance()->SetEnabled(false);
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$commit = $this->GetProject()->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $commit);
		$this->tpl->assign('tree', $commit->GetTree());
		$treediff = $commit->DiffToParent();
		$treediff->SetRenames(true);
		$this->tpl->assign('treediff', $treediff);
	}

}
