<?php
/**
 * Git blob load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Blob
 */
class GitPHP_BlobLoad_GitTest extends PHPUnit_Framework_TestCase
{
	public function testLoad()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->any())->method('GetPath')->will($this->returnValue(GITPHP_TEST_PROJECTROOT . '/testrepo.git'));
		$exemock = $this->getMock('GitPHP_GitExe');
		$exemock->expects($this->once())->method('Execute')->with($this->equalTo(GITPHP_TEST_PROJECTROOT . '/testrepo.git'), $this->equalTo('cat-file'))->will($this->returnValue("blob line 1\nblob line 2"));

		$blobmock = $this->getMockBuilder('GitPHP_Blob')->disableOriginalConstructor()->getMock();
		$blobmock->expects($this->any())->method('GetProject')->will($this->returnValue($projectmock));
		$blobmock->expects($this->any())->method('GetHash')->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));

		$strategy = new GitPHP_BlobLoad_Git($exemock);
		$this->assertEquals("blob line 1\nblob line 2", $strategy->Load($blobmock));
	}
}
