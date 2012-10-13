<?php
/**
 * User list class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Auth
 */
class GitPHP_UserList
{
	/**
	 * Stores the users
	 *
	 * @var array
	 */
	protected $users = array();

	/**
	 * Loads a user file
	 *
	 * @param string $userFile config file to load
	 */
	public function LoadUsers($userFile)
	{
		if (!is_readable($userFile))
			return;

		if (!include($userFile))
			return;

		if (isset($gitphp_users) && is_array($gitphp_users)) {
			foreach ($gitphp_users as $user) {
				if (empty($user['username']) || empty($user['password']))
					continue;

				$this->users[$user['username']] = new GitPHP_User($user['username'], $user['password']);
			}
		}
	}

	/**
	 * Get the user count
	 *
	 * @return int count
	 */
	public function GetCount()
	{
		return count($this->users);
	}

	/**
	 * Get a user
	 *
	 * @return GitPHP_User|null user object if found
	 * @param string $username username
	 */
	public function GetUser($username)
	{
		if (empty($username))
			return null;

		if (isset($this->users[$username]))
			return $this->users[$username];

		return null;
	}

	/**
	 * Add a user
	 *
	 * @param GitPHP_User $user user
	 */
	public function AddUser($user)
	{
		if (!$user)
			return;

		$username = $user->GetUsername();
		$password = $user->GetPassword();
		if (empty($username) || empty($password))
			return;

		$this->users[$username] = $user;
	}

	/**
	 * Remove a user
	 *
	 * @param string $username username
	 */
	public function RemoveUser($username)
	{
		if (empty($username))
			return;

		unset($this->users[$username]);
	}
}
