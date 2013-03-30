<?php
/**
 * Controller for displaying a commitdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commitdiff extends GitPHP_Controller_DiffBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->Plain()) {
			return 'commitdiffplain.tpl';
		}
		return 'commitdiff.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		$key = (isset($this->params['hash']) ? $this->params['hash'] : '')
		. '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
		. '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');

		return $key;
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
			return $this->resource->translate('commitdiff');
		}
		return 'commitdiff';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		parent::LoadHeaders();

		if ($this->Plain()) {
			$this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['hash'] . '.patch"';
			$this->preserveWhitespace = true;
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$co = $this->GetProject()->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $co);

		if (isset($this->params['hashparent'])) {
			$this->tpl->assign("hashparent", $this->params['hashparent']);
		}

		if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
			$this->tpl->assign('sidebyside', true);
		}

		$treediff = new GitPHP_TreeDiff($this->GetProject(), $this->exe, $this->params['hash'], (isset($this->params['hashparent']) ? $this->params['hashparent'] : ''));
		$this->tpl->assign('treediff', $treediff);
	}

}
