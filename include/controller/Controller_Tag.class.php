<?php
/**
 * Controller for displaying a tag
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Tag extends GitPHP_ControllerBase
{

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if (isset($this->params['jstip']) && $this->params['jstip']) {
			return 'tagtip.tpl';
		}
		return 'tag.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return isset($this->params['hash']) ? sha1($this->params['hash']) : '';
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
			return __('tag');
		}
		return 'tag';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (isset($_GET['h'])) {
			$this->params['hash'] = $_GET['h'];
		}

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
		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign('head', $head);

		$tag = $this->GetProject()->GetTagList()->GetTag($this->params['hash']);

		$this->tpl->assign("tag", $tag);
	}

}
