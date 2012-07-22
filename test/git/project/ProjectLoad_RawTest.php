<?php
/**
 * Raw project load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Project
 */
class GitPHP_ProjectLoad_RawTest extends PHPUnit_Framework_TestCase
{
	public function testLoadEpoch()
	{
		$commitmock = $this->getMockBuilder('GitPHP_Commit')->disableOriginalConstructor()->getMock();
		$commitmock->expects($this->any())->method('GetCommitterEpoch')->will($this->returnValue('1234'));
		$headmock = $this->getMockBuilder('GitPHP_Head')->disableOriginalConstructor()->getMock();
		$headmock->expects($this->any())->method('GetCommit')->will($this->returnValue($commitmock));

		$headlistmock = $this->getMockBuilder('GitPHP_HeadList')->disableOriginalConstructor()->getMock();
		$headlistmock->expects($this->once())->method('GetOrderedHeads')->with($this->equalTo('-committerdate'), $this->equalTo(1))->will($this->returnValue(array($headmock)));

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetHeadList')->will($this->returnValue($headlistmock));

		$strategy = new GitPHP_ProjectLoad_Raw($this->getMockBuilder('GitPHP_GitObjectLoader')->disableOriginalConstructor()->getMock());
		$this->assertEquals('1234', $strategy->LoadEpoch($projectmock));
	}

	public function testLoadHead()
	{
		$strategy = new GitPHP_ProjectLoad_Raw($this->getMockBuilder('GitPHP_GitObjectLoader')->disableOriginalConstructor()->getMock());


		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git');

		$headlistmock = $this->getMock('GitPHP_HeadList', array('Exists', 'GetHead'), array($project, $this->getMock('GitPHP_HeadListLoadStrategy_Interface')));
		$headmock = $this->getMock('GitPHP_Head', array('GetHash'), array($project, 'master'));
		$headmock->expects($this->any())->method('GetHash')->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));
		$headlistmock->expects($this->any())->method('Exists')->with($this->equalTo('master'))->will($this->returnValue(true));
		$headlistmock->expects($this->any())->method('GetHead')->with($this->equalTo('master'))->will($this->returnValue($headmock));
		$project->SetHeadList($headlistmock);
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $strategy->LoadHead($project));

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git');
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $strategy->LoadHead($project));
	}

	public function testExpandHash()
	{
		$loadermock = $this->getMockBuilder('GitPHP_GitObjectLoader')->disableOriginalConstructor()->getMock();
		$loadermock->expects($this->once())->method('ExpandHash')->with($this->equalTo('1234'))->will($this->returnValue('12345678'));

		$strategy = new GitPHP_ProjectLoad_Raw($loadermock);
		$this->assertEquals('12345678', $strategy->ExpandHash($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234'));
	}

	public function testAbbreviateHash()
	{
		$fullhash = '1234567890abcdef1234567890ABCDEF12345678';

		$loadermock = $this->getMockBuilder('GitPHP_GitObjectLoader')->disableOriginalConstructor()->GetMock();
		$loadermock->expects($this->once())->method('EnsureUniqueHash')->with($this->equalTo($fullhash), $this->equalTo('1234'))->will($this->returnValue('123456'));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetAbbreviateLength')->will($this->onConsecutiveCalls(null, 1, 45, 4));
		$projectmock->expects($this->any())->method('GetUniqueAbbreviation')->will($this->onConsecutiveCalls(false, false, false, true));

		$strategy = new GitPHP_ProjectLoad_Raw($loadermock);
		$this->assertEquals('1234567', $strategy->AbbreviateHash($projectmock, $fullhash));
		$this->assertEquals('1234', $strategy->AbbreviateHash($projectmock, $fullhash));
		$this->assertEquals($fullhash, $strategy->AbbreviateHash($projectmock, $fullhash));
		$this->assertEquals('123456', $strategy->AbbreviateHash($projectmock, $fullhash));
	}
}
