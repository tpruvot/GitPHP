<?php
/**
 * Base commit load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Commit
 */
class GitPHP_CommitLoad_BaseTest extends PHPUnit_Framework_TestCase
{
	public function testLoadContainingTag()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));

		$data = '1234567890abcdef1234567890ABCDEF12345678 tags/sometag^0';
		$data2 = '1234567890abcdef1234567890ABCDEF12345678 tags/other/tag~1';
		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->exactly(2))->method('Execute')->with(GITPHP_TEST_PROJECTROOT . '/testrepo.git', 'name-rev')->will($this->onConsecutiveCalls($data, $data2));

		$commitmock = $this->getMockBuilder('GitPHP_Commit')->disableOriginalConstructor()->getMock();
		$commitmock->expects($this->any())->method('GetProject')->will($this->returnValue($projectmock));
		$commitmock->expects($this->any())->method('GetHash')->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));

		$strategy = $this->getMockForAbstractClass('GitPHP_CommitLoad_Base', array($exemock));
		$this->assertEquals('sometag', $strategy->LoadContainingTag($commitmock));
		$this->assertEquals('other/tag', $strategy->LoadContainingTag($commitmock));
	}
}
