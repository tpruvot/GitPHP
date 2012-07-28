<?php
/**
 * Controller for displaying a blobdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blobdiff extends GitPHP_Controller_DiffBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->Plain()) {
			return 'blobdiffplain.tpl';
		}
		return 'blobdiff.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '') . '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');
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
			return $this->resource->translate('blobdiff');
		}
		return 'blobdiff';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		parent::LoadHeaders();

		if ($this->Plain()) {
			$this->preserveWhitespace = true;
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		if (isset($this->params['file']))
			$this->tpl->assign('file', $this->params['file']);

		$filediff = $this->GetProject()->GetObjectManager()->GetFileDiff($this->params['hashparent'], $this->params['hash']);
		$this->tpl->assign('filediff', $filediff);

		if ($this->Plain()) {
			return;
		}

		if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
			$this->tpl->assign('sidebyside', true);
		}

		$commit = $this->GetProject()->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		$blobparent = $this->GetProject()->GetObjectManager()->GetBlob($this->params['hashparent']);
		$blobparent->SetCommit($commit);
		$blobparent->SetPath($this->params['file']);
		$this->tpl->assign('blobparent', $blobparent);

		$blob = $this->GetProject()->GetObjectManager()->GetBlob($this->params['hash']);
		$blob->SetPath($this->params['file']);
		$this->tpl->assign('blob', $blob);

		$tree = $commit->GetTree();
		$this->tpl->assign('tree', $tree);
	}

}
