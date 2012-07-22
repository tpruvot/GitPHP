<?php
/**
 * Git project load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Project
 */
class GitPHP_ProjectLoad_GitTest extends PHPUnit_Framework_TestCase
{
	public function testLoadEpoch()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));

		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('for-each-ref'))->will($this->returnValue('Chris Han <xiphux@gmail.com> 1342244666 -0500'));

		$strategy = new GitPHP_ProjectLoad_Git($exemock);
		$this->assertEquals('1342244666', $strategy->LoadEpoch($projectmock));
	}

	public function testLoadHead()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));

		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('rev-parse'))->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));

		$strategy = new GitPHP_ProjectLoad_Git($exemock);
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $strategy->LoadHead($projectmock));
	}

	public function testExpandHash()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));

		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('rev-list'))->will($this->returnValue("commit 1234567890abcdef1234567890ABCDEF12345678\n1234567890abcdef1234567890ABCDEF12345678"));

		$strategy = new GitPHP_ProjectLoad_Git($exemock);
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $strategy->ExpandHash($projectmock, '12345678'));
	}

	public function testAbbreviateHash()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));

		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('rev-list'))->will($this->returnValue("commit 1234567890abcdef1234567890ABCDEF12345678\n12345678"));

		$strategy = new GitPHP_ProjectLoad_Git($exemock);
		$this->assertEquals('12345678', $strategy->AbbreviateHash($projectmock, '1234567890abcdef1234567890ABCDEF12345678'));
	}
}
