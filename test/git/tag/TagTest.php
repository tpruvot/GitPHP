<?php
/**
 * Tag test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Git\Tag
 */
class GitPHP_TagTest extends PHPUnit_Framework_TestCase
{
	public function testTagCommit()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('commit', $tag->GetType());
		$this->assertEquals('commit', $tag->GetType());		//cached
	}

	public function testTagTag()
	{
		$data = array(
			'tag',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abc123abc123abc123abc123abc123abc123abc1',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('tag', $tag->GetType());
		$this->assertEquals('tag', $tag->GetType());		//cached
	}

	public function testTagBlob()
	{
		$data = array(
			'blob',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			null,
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('blob', $tag->GetType());
		$this->assertEquals('blob', $tag->GetType());		//cached
	}

	public function testObject()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('abcdef1234567890ABCDEF1234567890abcdef12', $tag->GetObjectIdentifier());
		$this->assertEquals('abcdef1234567890ABCDEF1234567890abcdef12', $tag->GetObjectIdentifier());	//cached
	}

	public function testCommitHash()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('abcdef1234567890ABCDEF1234567890abcdef12', $tag->GetCommitHash());
		$this->assertEquals('abcdef1234567890ABCDEF1234567890abcdef12', $tag->GetCommitHash());	//cached

		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $this->getMock('GitPHP_TagLoadStrategy_Interface'));
		$tag->SetCommitHash('abc123abc123abc123abc123abc123abc123abc1');
		$this->assertEquals('abc123abc123abc123abc123abc123abc123abc1', $tag->GetCommitHash());
	}

	public function testTagger()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('tagger <taggeremail>', $tag->GetTagger());
		$this->assertEquals('tagger <taggeremail>', $tag->GetTagger());	//cached
	}

	public function testTaggerEpoch()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('1234567890', $tag->GetTaggerEpoch());
		$this->assertEquals('1234567890', $tag->GetTaggerEpoch());	//cached
	}

	public function testTaggerTimezone()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals('-0500', $tag->GetTaggerTimezone());
		$this->assertEquals('-0500', $tag->GetTaggerTimezone());	//cached
	}

	public function testComment()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertEquals(array('tag line 1', 'tag line 2'), $tag->GetComment());
		$this->assertEquals(array('tag line 1', 'tag line 2'), $tag->GetComment());	//cached
	}

	public function testLightTag()
	{
		$data = array(
			'commit',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'abcdef1234567890ABCDEF1234567890abcdef12',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertFalse($tag->LightTag());
		$this->assertFalse($tag->LightTag());	//cached

		$data = array(
			'commit',
			'1234567890abcdef1234567890ABCDEF12345678',
			'1234567890abcdef1234567890ABCDEF12345678',
			'tagger <taggeremail>',
			'1234567890',
			'-0500',
			array(
				'tag line 1',
				'tag line 2'
			)
		);
		$strategymock = $this->getMock('GitPHP_TagLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Tag'))->will($this->returnValue($data));
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $strategymock, '1234567890abcdef1234567890ABCDEF12345678');

		$this->assertTrue($tag->LightTag());
		$this->assertTrue($tag->LightTag());	//cached
	}

	public function testObserver()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$tag = new GitPHP_Tag($projectmock, 'sometag', $this->getMock('GitPHP_TagLoadStrategy_Interface'));

		$observermock = $this->getMock('GitPHP_Observer_Interface');
		$matcher = $this->once();
		$observermock->expects($matcher)->method('ObjectChanged')->with($this->isInstanceOf('GitPHP_Tag'), $this->equalTo(GitPHP_Observer_Interface::CacheableDataChange));

		$tag->AddObserver($observermock);
		$tagger = $tag->GetTagger();
		$tagger = $tag->GetTagger();	//cached

		$this->assertEquals(1, $matcher->getInvocationCount());
	}

}
