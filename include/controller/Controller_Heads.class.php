<?php
/**
 * Controller for displaying heads
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Heads extends GitPHP_ControllerBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'heads.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return '';
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
			return __('heads');
		}
		return 'heads';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign("head",$head);

		$headlist = $this->GetProject()->GetHeadList()->GetOrderedHeads('-committerdate');
		if (isset($headlist) && (count($headlist) > 0)) {
			$this->tpl->assign("headlist", $headlist);
		}
	}

}
