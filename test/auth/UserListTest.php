<?php
/**
 * Userlist test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Auth
 */
class GitPHP_UserListTest extends PHPUnit_Framework_TestCase
{
	public function testUserList()
	{
		$userlist = new GitPHP_UserList();

		$this->assertEquals(0, $userlist->GetCount());

		$user = new GitPHP_User('username', 'password');
		$userlist->AddUser($user);

		$this->assertEquals(1, $userlist->GetCount());

		$user2 = $userlist->GetUser('username');
		$this->assertInstanceOf('GitPHP_User', $user2);
		$this->assertEquals('username', $user2->GetUsername());
		$this->assertEquals('password', $user2->GetPassword());

		$userlist->RemoveUser('username');
		$this->assertNull($userlist->GetUser('username'));
		$this->assertEquals(0, $userlist->GetCount());
	}
}
