<?php
/**
 * Raw blob load test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Test\Blob
 */
class GitPHP_BlobLoad_RawTest extends PHPUnit_Framework_TestCase
{
	public function testLoad()
	{
		$blobmock = $this->getMockBuilder('GitPHP_Blob')->disableOriginalConstructor()->getMock();
		$blobmock->expects($this->any())->method('GetHash')->will($this->returnValue('1234567890abcdef1234567890ABCDEF12345678'));
		$loadermock = $this->getMockBuilder('GitPHP_GitObjectLoader')->disableOriginalConstructor()->getMock();
		$loadermock->expects($this->once())->method('GetObject')->with($this->equalTo('1234567890abcdef1234567890ABCDEF12345678'))->will($this->returnValue("blob line 1\nblob line 2"));
		$exemock = $this->getMock('GitPHP_GitExe');

		$strategy = new GitPHP_BlobLoad_Raw($loadermock, $exemock);
		$this->assertEquals("blob line 1\nblob line 2", $strategy->Load($blobmock));
	}
}
