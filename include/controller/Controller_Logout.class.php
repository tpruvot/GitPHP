<?php
/**
 * Controller for logout
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Logout extends GitPHP_ControllerBase
{
	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeUserList();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		return 'logout';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if (!empty($_SESSION['gitphpuser'])) {
			unset($_SESSION['gitphpuser']);
		}
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$this->headers[] = 'Location: ' . $_SERVER['HTTP_REFERER'];
		} else {
			$this->headers[] = 'Location: ' . $this->router->GetUrl(array(), true);
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
	}

	/**
	 * Renders the output
	 */
	public function Render()
	{
	}

}
