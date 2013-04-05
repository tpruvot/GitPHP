<?php
/**
 * Controller for displaying tags
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Tags extends GitPHP_ControllerBase
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
		return 'tags.tpl';
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
			return $this->resource->translate('tags');
		}
		return 'tags';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign("head",$head);

		$this->tpl->assign('page', $this->params['page']);
		$skip = $this->params['page'] * 100;

		$taglist = $this->GetProject()->GetTagList()->GetOrderedTags('-creatordate', 101, $skip);
		if (isset($taglist) && (count($taglist) > 0)) {
			if (count($taglist) > 100) {
				$taglist = array_slice($taglist, 0, 100);
				$this->tpl->assign('hasmoretags', true);
			}
			$this->tpl->assign("taglist",$taglist);
		}
	}

}
