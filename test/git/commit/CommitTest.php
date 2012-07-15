<?php
/**
 * Commit test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Git\Commit
 */
class GitPHP_CommitTest extends PHPUnit_Framework_TestCase
{
	private function getMockCommitLoader()
	{
		$commitdata = array(
			'abbrevhash',
			'treehash',
			array(
				'parent1',
				'parent2'
			),
			'author <authoremail>',
			'12345678',
			'-0500',
			'committer <committeremail>',
			'87654321',
			'-0600',
			'commit line 1 commit line 1 commit line 1 commit line 1',
			array(
				'commit line 1 commit line 1 commit line 1 commit line 1',
				'commit line 2'
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		return $loadstrategy;
	}

	public function testGetHash()
	{
		$loadstrategy = $this->getMockCommitLoader();
		$loadstrategy->expects($this->any())->method('LoadsAbbreviatedHash')->will($this->returnValue(true));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);

		$this->assertEquals('abbrevhash', $commit->GetHash(true));
		$this->assertEquals('abbrevhash', $commit->GetHash(true));	//cached
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $commit->GetHash());
	}

	public function testGetParent()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());

		$this->assertEquals('parent1', $commit->GetParentHash());
		$this->assertEquals('parent1', $commit->GetParentHash());	//cached
	}

	public function testGetParents()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals(array('parent1', 'parent2'), $commit->GetParentHashes());
		$this->assertEquals(array('parent1', 'parent2'), $commit->GetParentHashes());	//cached
	}

	public function testGetTree()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('treehash', $commit->GetTreeHash());
		$this->assertEquals('treehash', $commit->GetTreeHash());	//cached
	}

	public function testGetAuthor()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('author <authoremail>', $commit->GetAuthor());
		$this->assertEquals('author <authoremail>', $commit->GetAuthor());	//cached
	}

	public function testGetAuthorName()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('author', $commit->GetAuthorName());
		$this->assertEquals('author', $commit->GetAuthorName());	//cached
	}

	public function testGetAuthorEpoch()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('12345678', $commit->GetAuthorEpoch());
		$this->assertEquals('12345678', $commit->GetAuthorEpoch());	//cached
	}

	public function testGetAuthorTimezone()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('-0500', $commit->GetAuthorTimezone());
		$this->assertEquals('-0500', $commit->GetAuthorTimezone());	//cached
	}

	public function testGetCommitter()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('committer <committeremail>', $commit->GetCommitter());
		$this->assertEquals('committer <committeremail>', $commit->GetCommitter());	//cached
	}

	public function testGetCommitterName()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('committer', $commit->GetCommitterName());
		$this->assertEquals('committer', $commit->GetCommitterName());	//cached
	}

	public function testGetCommitterEpoch()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('87654321', $commit->GetCommitterEpoch());
		$this->assertEquals('87654321', $commit->GetCommitterEpoch());	//cached
	}

	public function testGetCommitterTimezone()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('-0600', $commit->GetCommitterTimezone());
		$this->assertEquals('-0600', $commit->GetCommitterTimezone());	//cached
	}

	public function testGetTitle()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals('commit line 1 commit line 1 commit line 1 commit line 1', $commit->GetTitle());
		$this->assertEquals('commit line 1 commit line 1 commit line 1 commit line 1', $commit->GetTitle());	//cached
		$this->assertEquals('commit lin…', $commit->GetTitle(13));
	}

	public function testGetComment()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals(array('commit line 1 commit line 1 commit line 1 commit line 1', 'commit line 2'), $commit->GetComment());
		$this->assertEquals(array('commit line 1 commit line 1 commit line 1 commit line 1', 'commit line 2'), $commit->GetComment());	//cached
	}

	public function testSearchComment()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());
		$this->assertEquals(array('commit line 1 commit line 1 commit line 1 commit line 1'), $commit->SearchComment('line 1'));
	}

	public function testMergeCommit()
	{
		$commitdata = array(
			'abbrevhash',
			'treehash',
			array(
				'parent1',
				'parent2'
			),
			'author <authoremail>',
			'12345678',
			'-0500',
			'committer <committeremail>',
			'87654321',
			'-0600',
			'commit line 1 commit line 1 commit line 1 commit line 1',
			array(
				'commit line 1 commit line 1 commit line 1 commit line 1',
				'commit line 2'
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);
		$this->assertTrue($commit->IsMergeCommit());
		$this->assertTrue($commit->IsMergeCommit());	//cached

		$commitdata = array(
			'abbrevhash',
			'treehash',
			array(
				'parent1'
			),
			'author <authoremail>',
			'12345678',
			'-0500',
			'committer <committeremail>',
			'87654321',
			'-0600',
			'commit line 1 commit line 1 commit line 1 commit line 1',
			array(
				'commit line 1 commit line 1 commit line 1 commit line 1',
				'commit line 2'
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);
		$this->assertFalse($commit->IsMergeCommit());
		$this->assertFalse($commit->IsMergeCommit());	//cached
	}

	public function testContainingTagLoad()
	{
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('LoadContainingTag')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue('containingtag'));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);
		$this->assertEquals('containingtag', $commit->GetContainingTagName());
		$this->assertEquals('containingtag', $commit->GetContainingTagName());		//cached
	}

	public function testObserverNotification()
	{
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $this->getMockCommitLoader());

		$observer = $this->getMock('GitPHP_Observer_Interface');
		$matcher = $this->once();
		$observer->expects($matcher)->method('ObjectChanged')->with($this->isInstanceOf('GitPHP_Commit'), $this->equalTo(GitPHP_Observer_Interface::CacheableDataChange));

		$commit->AddObserver($observer);
		$title = $commit->GetTitle();
		$title = $commit->GetTitle();	//cached
		
		$this->assertEquals(1, $matcher->getInvocationCount());
	}

	public function testCompareAuthorEpoch()
	{
		$commitdata = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			null,
			null,
			null,
			array(
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);

		$commitdata2 = array(
			null,
			null,
			array(
			),
			null,
			'12345679',
			null,
			null,
			null,
			null,
			null,
			array(
			)
		);
		$loadstrategy2 = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy2->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata2));
		$commit2 = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy2);

		$this->assertLessThan(0, GitPHP_Commit::CompareAuthorEpoch($commit, $commit2));
		$this->assertGreaterThan(0, GitPHP_Commit::CompareAuthorEpoch($commit2, $commit));



		$commitdata = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			null,
			null,
			null,
			array(
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);

		$commitdata2 = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			null,
			null,
			null,
			array(
			)
		);
		$loadstrategy2 = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy2->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata2));
		$commit2 = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy2);

		$this->assertEquals(0, GitPHP_Commit::CompareAuthorEpoch($commit, $commit2));
		$this->assertEquals(0, GitPHP_Commit::CompareAuthorEpoch($commit2, $commit));

	}

	public function testCompareAge()
	{
		$commitdata = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			'2',
			null,
			null,
			array(
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);

		$commitdata2 = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			'1',
			null,
			null,
			array(
			)
		);
		$loadstrategy2 = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy2->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata2));
		$commit2 = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy2);

		$this->assertLessThan(0, GitPHP_Commit::CompareAge($commit, $commit2));
		$this->assertGreaterThan(0, GitPHP_Commit::CompareAge($commit2, $commit));



		$commitdata = array(
			null,
			null,
			array(
			),
			null,
			'12345678',
			null,
			null,
			'1',
			null,
			null,
			array(
			)
		);
		$loadstrategy = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata));
		$commit = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy);

		$commitdata2 = array(
			null,
			null,
			array(
			),
			null,
			'12345679',
			null,
			null,
			'1',
			null,
			null,
			array(
			)
		);
		$loadstrategy2 = $this->getMock('GitPHP_CommitLoadStrategy_Interface');
		$loadstrategy2->expects($this->once())->method('Load')->with($this->isInstanceOf('GitPHP_Commit'))->will($this->returnValue($commitdata2));
		$commit2 = new GitPHP_Commit($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678', $loadstrategy2);

		$this->assertGreaterThan(0, GitPHP_Commit::CompareAge($commit, $commit2));
		$this->assertLessThan(0, GitPHP_Commit::CompareAge($commit2, $commit));
	}

}
