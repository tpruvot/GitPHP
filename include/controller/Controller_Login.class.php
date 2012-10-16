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
		if (isset($this->params['output']) && ($this->params['output'] == 'js')) {
			$this->headers[] = 'Content-Type: application/json';
			$this->DisableLogging();
		}

		if (!empty($_SESSION['gitphpuser'])) {
			$user = $this->userList->GetUser($_SESSION['gitphpuser']);
			if ($user) {
				if (!(isset($this->params['output']) && ($this->params['output'] == 'js'))) {
					if (!empty($this->params['redirect']))
						$this->headers[] = 'Location: ' . $this->params['redirect'];
					else
						$this->headers[] = 'Location: ' . $this->router->GetUrl(array(), true);
					$this->DisableLogging();
				}
				$this->loginSuccess = true;
			} else {
				unset($_SESSION['gitphpuser']);
			}
		}

		if (!(empty($this->params['username']) || empty($this->params['password']))) {
			$user = $this->userList->GetUser($this->params['username']);
			if ($user && ($this->params['password'] === $user->GetPassword())) {
				$_SESSION['gitphpuser'] = $user->GetUsername();
				if (!(isset($this->params['output']) && ($this->params['output'] == 'js'))) {
					if (!empty($this->params['redirect']))
						$this->headers[] = 'Location: ' . $this->params['redirect'];
					else
						$this->headers[] = 'Location: ' . $this->router->GetUrl(array(), true);
					$this->DisableLogging();
				}
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
		if (!(isset($this->params['output']) && ($this->params['output'] == 'js'))) {
			if ($this->loginSuccess === false) {
				if ($this->resource) {
					$this->tpl->assign('loginerror', $this->resource->translate('Invalid username or password'));
				} else {
					$this->tpl->assign('loginerror', 'Invalid username or password');
				}
			}
			if (!empty($this->params['redirect'])) {
				$this->tpl->assign('redirect', $this->params['redirect']);
			} else if (!empty($_SERVER['HTTP_REFERER'])) {
				$this->tpl->assign('redirect', $_SERVER['HTTP_REFERER']);
			}
		}
	}

	/**
	 * Renders the output
	 */
	public function Render()
	{
		if (isset($this->params['output']) && ($this->params['output'] == 'js')) {
			$result = array();
			if ($this->loginSuccess === true)
				$result['success'] = true;
			else {
				$result['success'] = false;
				if ($this->loginSuccess === false) {
					if ($this->resource) {
						$result['message'] = $this->resource->translate('Invalid username or password');
					} else {
						$result['message'] = 'Invalid username or password';
					}
				}
			}
			echo json_encode($result);
			return;
		}

		if ($this->loginSuccess === true)
			return;				// logged in and redirected, don't render

		return parent::Render();
	}

}
