<?php
/**
 * Git commit load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Commit
 */
class GitPHP_CommitLoad_GitTest extends PHPUnit_Framework_TestCase
{
	public function testLoad()
	{
		/*
		$exedata = "1234567 f1fd111d4d59ec053ed2f33322e90dba72d677c5 332a7ec90e4bbcd4147c06a6128920e74e443609\n" .
		"tree 0cbcbafede205ab07ca19e22663661cb8c8bf2aa\n" .
		"parent f1fd111d4d59ec053ed2f33322e90dba72d677c5\n" .
		"parent 332a7ec90e4bbcd4147c06a6128920e74e443609\n" .
		"author author <authoremail> 1234567890 -0500\n" .
		"committer committer <committeremail> 0987654321 -0600\n\n" .
		"    Message line 1\n" .
		"    Message line 2\n";

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));
		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('rev-list'))->will($this->returnValue($exedata));

		$commitmock = $this->getMockBuilder('GitPHP_Commit')->disableOriginalConstructor()->getMock();
		$commitmock->expects($this->any())->method('GetProject')->will($this->returnValue($projectmock));
		$commitmock->expects($this->any())->method('GetHash')->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));

		$strategy = new GitPHP_CommitLoad_Git($exemock);
		$commitdata = $strategy->Load($commitmock);

		$this->assertCount(11, $commitdata);

		$this->assertEquals('1234567', $commitdata[0]);
		$this->assertEquals('0cbcbafede205ab07ca19e22663661cb8c8bf2aa', $commitdata[1]);
		$this->assertEquals(array("f1fd111d4d59ec053ed2f33322e90dba72d677c5", "332a7ec90e4bbcd4147c06a6128920e74e443609"), $commitdata[2]);
		$this->assertEquals('author <authoremail>', $commitdata[3]);
		$this->assertEquals('1234567890', $commitdata[4]);
		$this->assertEquals('-0500', $commitdata[5]);
		$this->assertEquals('committer <committeremail>', $commitdata[6]);
		$this->assertEquals('0987654321', $commitdata[7]);
		$this->assertEquals('-0600', $commitdata[8]);
		$this->assertEquals('Message line 1', $commitdata[9]);
		$this->assertEquals(array('Message line 1', 'Message line 2'), $commitdata[10]);
		*/
	}
}
