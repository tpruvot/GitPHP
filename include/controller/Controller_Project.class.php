<?php
/**
 * GitPHP Controller Project
 *
 * Controller for displaying a project summary
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Project controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Project extends GitPHP_ControllerBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'project.tpl';
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
		if ($local && $this->resource) {
			return $this->resource->translate('summary');
		}
		return 'summary';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$this->tpl->assign('head', $this->GetProject()->GetHeadCommit());

		$revlist = $this->GetProject()->GetLog('HEAD', 17);
		if ($revlist) {
			if (count($revlist) > 16) {
				$this->tpl->assign('hasmorerevs', true);
				$revlist = array_slice($revlist, 0, 16);
			}
			$this->tpl->assign('revlist', $revlist);
		}

		$taglist = $this->GetProject()->GetTags(7);
		if ($taglist) {
			if (count($taglist) > 6) {
				$this->tpl->assign('hasmoretags', true);
				$taglist = array_slice($taglist, 0, 6);
			}
			$this->tpl->assign('taglist', $taglist);
		}

		$headlist = $this->GetProject()->GetHeads(17);
		if ($headlist) {
			if (count($headlist) > 17) {
				$this->tpl->assign('hasmoreheads', true);
				$headlist = array_slice($headlist, 0, 16);
			}
			$this->tpl->assign('headlist', $headlist);
		}

		$remotelist = $this->GetProject()->GetRemotes(10);
		if ($remotelist) {
			if (count($remotelist) > 9) {
				$this->tpl->assign('hasmoreremotes', true);
				$remotelist = array_slice($remotelist, 0, 9);
			}
			$this->tpl->assign('remotelist', $remotelist);
		}
	}

}
