<?php
/**
 * Base git object test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Git
 */
class GitPHP_GitObjectTest extends PHPUnit_Framework_TestCase
{
	public function testHash()
	{
		$object = $this->getMockForAbstractClass('GitPHP_GitObject', array($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), '1234567890abcdef1234567890ABCDEF12345678'));
		$this->assertEquals('1234567890abcdef1234567890ABCDEF12345678', $object->GetHash());
	}

	public function testInvalidHash()
	{
		$this->setExpectedException('GitPHP_InvalidHashException');

		$object = $this->getMockForAbstractClass('GitPHP_GitObject', array($this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock(), 'invalidhash'));
	}

	public function testAbbreviateHash()
	{
		$projectmock = $this->getMockBuilder('GitPHP_Project')->disableOriginalConstructor()->getMock();
		$projectmock->expects($this->once())->method('AbbreviateHash')->with($this->equalTo('1234567890abcdef1234567890ABCDEF12345678'))->will($this->returnValue('12345678'));

		$object = $this->getMockForAbstractClass('GitPHP_GitObject', array($projectmock, '1234567890abcdef1234567890ABCDEF12345678'));

		$this->assertEquals('12345678', $object->GetHash(true));
		$this->assertEquals('12345678', $object->GetHash(true));	//cached
	}
}
