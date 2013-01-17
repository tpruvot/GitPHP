<?php
/**
 * Blob test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Git\Blob
 */
class GitPHP_BlobTest extends PHPUnit_Framework_TestCase
{
	public function testLoad()
	{
		$strategymock = $this->getMock('GitPHP_BlobLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Blob'))->will($this->returnValue("data line 1\ndata line 2"));

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $strategymock);
		$this->assertEquals("data line 1\ndata line 2", $blob->GetData());
		$this->assertEquals("data line 1\ndata line 2", $blob->GetData());	//cached
		$this->assertEquals(array('data line 1', 'data line 2'), $blob->GetData(true));
	}

	public function testSize()
	{
		$strategymock = $this->getMock('GitPHP_BlobLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Size')->with($this->isInstanceOf('GitPHP_Blob'))->will($this->returnValue(23));

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $strategymock);
		$this->assertEquals(23, $blob->GetSize());
		$this->assertEquals(23, $blob->GetSize());	//cached

		$strategymock = $this->getMock('GitPHP_BlobLoadStrategy_Interface');
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $strategymock);
		$blob->SetSize(100);
		$this->assertEquals(100, $blob->GetSize());
	}

	public function testBinary()
	{
		$strategymock = $this->getMock('GitPHP_BlobLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Blob'))->will($this->returnValue("data line 1\ndata line 2"));

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $strategymock);
		$this->assertFalse($blob->IsBinary());
		$this->assertFalse($blob->IsBinary());	// cached

		$strategymock = $this->getMock('GitPHP_BlobLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Blob'))->will($this->returnValue("binary data" . chr(0) . "binary data"));

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $strategymock);
		$this->assertTrue($blob->IsBinary());
		$this->assertTrue($blob->IsBinary());	// cached
	}

	public function testObserver()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$blob = new GitPHP_Blob($projectmock, '1234567890abcdef1234567890ABCDEF12345678', $this->getMock('GitPHP_BlobLoadStrategy_Interface'));

		$observermock = $this->getMock('GitPHP_Observer_Interface');
		$matcher = $this->once();
		$observermock->expects($matcher)->method('ObjectChanged')->with($this->isInstanceOf('GitPHP_Blob'), $this->equalTo(GitPHP_Observer_Interface::CacheableDataChange));

		$blob->AddObserver($observermock);
		$data = $blob->GetData();
		$data = $blob->GetData();	//cached

		$this->assertEquals(1, $matcher->getInvocationCount());
	}
}
