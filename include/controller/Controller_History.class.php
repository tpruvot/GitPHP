<?php
/**
 * Controller for displaying file history
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_History extends GitPHP_ControllerBase
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
		return 'history.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '') . '|' . $this->params['page'];
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
			return $this->resource->translate('history');
		}
		return 'history';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$co = $this->GetProject()->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $co);
		$tree = $co->GetTree();
		$this->tpl->assign('tree', $co->GetTree());

		$blobhash = $tree->PathToHash($this->params['file']);
		if (empty($blobhash))
			throw new GitPHP_FileNotFoundException($this->params['file']);
		$blob = $this->GetProject()->GetObjectManager()->GetBlob($blobhash);
		$blob->SetCommit($co);
		$blob->SetPath($this->params['file']);
		$this->tpl->assign('blob', $blob);

		$this->tpl->assign('page',$this->params['page']);
		$skip = $this->params['page'] * 100;

		$history = new GitPHP_FileHistory($this->GetProject(), $this->params['file'], $this->exe, $co, 101, $skip);
		if ($history->GetCount() > 100) {
			$this->tpl->assign('hasmorehistory', true);
			$history->SetLimit(100);
		}
		$this->tpl->assign('history', $history);
	}

}
