<?php
/**
 * User test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Auth
 */
class GitPHP_UserTest extends PHPUnit_Framework_TestCase
{
	public function testUser()
	{
		$user = new GitPHP_User('user','pass');
		$this->assertEquals('user', $user->GetUsername());
		$this->assertEquals('pass', $user->GetPassword());
		
		$user->SetUsername('user2');
		$this->assertEquals('user2', $user->GetUsername());
		$user->SetPassword('pass2');
		$this->assertEquals('pass2', $user->GetPassword());
	}
}
