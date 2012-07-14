<?php
/**
 * Project test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage GitPHP\Test\Project
 */
class GitPHP_ProjectTest extends PHPUnit_Framework_TestCase
{
	public function testInvalidDirectory()
	{
		$this->setExpectedException('GitPHP_InvalidDirectoryException');

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'nonexistentproject.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
	}

	public function testInvalidRepo()
	{
		$this->setExpectedException('GitPHP_InvalidGitRepositoryException');

		$project = new GitPHP_Project(GITPHP_TEST_RESOURCES, 'testdir', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
	}

	public function testDirectoryTraversal()
	{
		$this->setExpectedException('GitPHP_DirectoryTraversalException');

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, '../externalrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
	}

	public function testSlug()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'category/subrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));

		$this->assertEquals('category-subrepo', $project->GetSlug());
	}

	public function testPath()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));

		$this->assertEquals(GITPHP_TEST_PROJECTROOT . '/testrepo.git', $project->GetPath());
	}

	public function testDescription()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));

		$this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris tempus commodo libero, ut molestie est molestie vel. Proin molestie sodales elit bibendum aliquam. Vestibulum venenatis purus convallis tortor sodales vel scelerisque justo bibendum. Pellentesque egestas, sapien eu pulvinar suscipit, erat nisi mollis sapien, a lacinia urna lectus nec lectus. Fusce ornare diam a quam faucibus auctor aliquet nunc tincidunt. Sed massa tellus, feugiat eu iaculis id, blandit eget nisi. In suscipit commodo erat, et blandit nisl lacinia non. Sed quis tortor nisl. Phasellus egestas sapien nec elit tempor a tempor lectus tincidunt.', $project->GetDescription());
		$this->assertEquals('Lorem ipsum dolor si…', $project->GetDescription(20));
	}

	public function testDaemonEnabled()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$this->assertFalse($project->GetDaemonEnabled());

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$this->assertTrue($project->GetDaemonEnabled());
	}

	public function testHead()
	{
		$strategymock = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('LoadHead')->with($this->isInstanceOf('GitPHP_Project'))->will($this->returnValue('refs/heads/master'));

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $strategymock);
		$this->assertEquals('refs/heads/master', $project->GetHeadReference());
	}

	public function testEpoch()
	{
		$strategymock = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('LoadEpoch')->with($this->isInstanceOf('GitPHP_Project'))->will($this->returnValue('12345678'));

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $strategymock);
		$this->assertEquals('12345678', $project->GetEpoch());
	}

	public function testAbbreviateHash()
	{
		$strategymock = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('AbbreviateHash')->with($this->isInstanceOf('GitPHP_Project'), $this->equalTo('longhash'))->will($this->returnValue('shorthash'));

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $strategymock);
		$this->assertEquals('shorthash', $project->AbbreviateHash('longhash'));
	}

	public function testExpandHash()
	{
		$strategymock = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('ExpandHash')->with($this->isInstanceOf('GitPHP_Project'), $this->equalTo('shorthash'))->will($this->returnValue('longhash'));

		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $strategymock);
		$this->assertEquals('longhash', $project->ExpandHash('shorthash'));
	}

	public function testCompareProject()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$project2 = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));

		$this->assertEquals(0, GitPHP_Project::CompareProject($project, $project));
		$this->assertLessThan(0, GitPHP_Project::CompareProject($project, $project2));
		$this->assertGreaterThan(0, GitPHP_Project::CompareProject($project2, $project));

		$project->SetCategory('b');
		$this->assertEquals(0, GitPHP_Project::CompareProject($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareProject($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareProject($project2, $project));

		$project2->SetCategory('a');
		$this->assertEquals(0, GitPHP_Project::CompareProject($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareProject($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareProject($project2, $project));
	}

	public function testCompareDescription()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$project->SetDescription('A description');
		$project2 = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$project->SetDescription('B description');

		$this->assertEquals(0, GitPHP_Project::CompareDescription($project, $project));
		$this->assertLessThan(0, GitPHP_Project::CompareDescription($project, $project2));
		$this->assertGreaterThan(0, GitPHP_Project::CompareDescription($project2, $project));

		$project->SetCategory('b');
		$this->assertEquals(0, GitPHP_Project::CompareDescription($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareDescription($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareDescription($project2, $project));

		$project2->SetCategory('a');
		$this->assertEquals(0, GitPHP_Project::CompareDescription($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareDescription($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareDescription($project2, $project));
	}

	public function testCompareOwner()
	{
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$project->SetOwner('A owner');
		$project2 = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $this->getMock('GitPHP_ProjectLoadStrategy_Interface'));
		$project->SetOwner('B owner');

		$this->assertEquals(0, GitPHP_Project::CompareOwner($project, $project));
		$this->assertLessThan(0, GitPHP_Project::CompareOwner($project, $project2));
		$this->assertGreaterThan(0, GitPHP_Project::CompareOwner($project2, $project));

		$project->SetCategory('b');
		$this->assertEquals(0, GitPHP_Project::CompareOwner($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareOwner($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareOwner($project2, $project));

		$project2->SetCategory('a');
		$this->assertEquals(0, GitPHP_Project::CompareOwner($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareOwner($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareOwner($project2, $project));
	}

	public function testCompareAge()
	{
		$strategymock = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock->expects($this->once())->method('LoadEpoch')->with($this->isInstanceOf('GitPHP_Project'))->will($this->returnValue('2'));
		$project = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepoexported.git', $strategymock);

		$strategymock2 = $this->getMock('GitPHP_ProjectLoadStrategy_Interface');
		$strategymock2->expects($this->once())->method('LoadEpoch')->with($this->isInstanceOf('GitPHP_Project'))->will($this->returnValue('1'));
		$project2 = new GitPHP_Project(GITPHP_TEST_PROJECTROOT, 'testrepo.git', $strategymock2);

		$this->assertEquals(0, GitPHP_Project::CompareAge($project, $project));
		$this->assertLessThan(0, GitPHP_Project::CompareAge($project, $project2));
		$this->assertGreaterThan(0, GitPHP_Project::CompareAge($project2, $project));

		$project->SetCategory('b');
		$this->assertEquals(0, GitPHP_Project::CompareAge($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareAge($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareAge($project2, $project));

		$project2->SetCategory('a');
		$this->assertEquals(0, GitPHP_Project::CompareAge($project, $project));
		$this->assertGreaterThan(0, GitPHP_Project::CompareAge($project, $project2));
		$this->assertLessThan(0, GitPHP_Project::CompareAge($project2, $project));
	}

}
