<?php
/**
 * Controller for login
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Login extends GitPHP_ControllerBase
{
	/**
	 * Flag whether login was successful
	 *
	 * @var boolean|null
	 */
	protected $loginSuccess = null;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeConfig();

		$this->InitializeResource();

		$this->InitializeUserList();

		$this->EnableLogging();

		$this->InitializeSmarty();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'login.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		$key = (isset($this->params['username']) ? $this->params['username'] : '') . '|' . (isset($this->params['password']) ? $this->params['password'] : '');
		if (!empty($key))
			$key = sha1($key);
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
			return $this->resource->translate('login');
		}
		return 'login';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if (!empty($_SESSION['gitphpuser'])) {
			$user = $this->userList->GetUser($_SESSION['gitphpuser']);
			if ($user) {
				$this->headers[] = 'Location: ' . $this->router->GetUrl(array(), true);
				$this->loginSuccess = true;
			} else {
				unset($_SESSION['gitphpuser']);
			}
		}

		if (!(empty($this->params['username']) || empty($this->params['password']))) {
			$user = $this->userList->GetUser($this->params['username']);
			if ($user && ($this->params['password'] === $user->GetPassword())) {
				$_SESSION['gitphpuser'] = $user->GetUsername();
				$this->headers[] = 'Location: ' . $this->router->GetUrl(array(), true);
				$this->loginSuccess = true;
			} else {
				$this->loginSuccess = false;
			}
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		if ($this->loginSuccess === false) {
			$this->tpl->assign('loginerror', 'Invalid username or password');
		}
	}

	/**
	 * Renders the output
	 */
	public function Render()
	{
		if ($this->loginSuccess === true)
			return;				// logged in and redirected, don't render

		return parent::Render();
	}

}
