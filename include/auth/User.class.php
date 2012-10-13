<?php
/**
 * User class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Auth
 */
class GitPHP_User
{
	/**
	 * Username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Constructor
	 *
	 * @param string $username username
	 * @param string $password password
	 */
	public function __construct($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Get the username
	 *
	 * @return string username
	 */
	public function GetUsername()
	{
		return $this->username;
	}

	/**
	 * Set the username
	 *
	 * @param string $username username
	 */
	public function SetUsername($username)
	{
		$this->username = $username;
	}

	/**
	 * Get the password
	 *
	 * @return string password
	 */
	public function GetPassword()
	{
		return $this->password;
	}

	/**
	 * Set the password
	 *
	 * @param string $password password
	 */
	public function SetPassword($password)
	{
		$this->password = $password;
	}
}
