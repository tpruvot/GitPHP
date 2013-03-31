<?php
/**
 * Route test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test\Router
 */
class GitPHP_RouteTest extends PHPUnit_Framework_TestCase
{
	public function testPath()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2');
		$this->assertEquals('test/:param1/route/:param2', $route->GetPath());
	}

	public function testConstraints()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2', array('param1' => 'testvalue', 'param2' => 'testvalue2|testvalue3'));

		$params = array(
			'param1' => 'testvalue'
		);

		$this->assertFalse($route->Valid($params));

		$params['param2'] = 'badvalue';

		$this->assertFalse($route->Valid($params));

		$params['param2'] = 'testvalue2';

		$this->assertTrue($route->Valid($params));

		$params['param2'] = 'testvalue3';

		$this->assertTrue($route->Valid($params));
	}

	public function testUsedParams()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2', array(), array('otherparam' => 'othervalue', 'otherparam2' => 'othervalue2'));

		$params = $route->GetUsedParameters();
		$this->assertCount(4, $params);
		$this->assertContains('param1', $params);
		$this->assertContains('param2', $params);
		$this->assertContains('otherparam', $params);
		$this->assertContains('otherparam2', $params);
	}

	public function testMatch()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2', array('param1' => 'validvalue'), array('extraparam' => 'extravalue'));

		$path = 'test/invalid';
		$this->assertFalse($route->Match($path));

		$path = 'test/invalidvalue/route/otherparam';
		$this->assertFalse($route->Match($path));

		$path = 'test/validvalue/route/otherparam';
		$params = $route->Match($path);

		$this->assertCount(3, $params);
		$this->assertEquals('validvalue', $params['param1']);
		$this->assertEquals('otherparam', $params['param2']);
		$this->assertEquals('extravalue', $params['extraparam']);
	}

	public function testBuild()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2');
		$params = array(
			'param1' => 'testvalue',
			'param2' => 'testvalue2'
		);

		$this->assertEquals('test/testvalue/route/testvalue2', $route->Build($params));
	}

	public function testParent()
	{
		$parentroute = new GitPHP_Route('parent/:parent', array('parent' => 'parentvalue'), array('parentparam' => 'parentvalue'));

		$childroute = new GitPHP_Route('child/:child', array('child' => 'childvalue'), array('childparam' => 'childvalue'), $parentroute);

		$this->assertEquals('parent/:parent/child/:child', $childroute->GetPath());

		$params = array(
			'child' => 'childvalue'
		);

		$this->assertFalse($childroute->Valid($params));

		$params['parent'] = 'parentvalue';

		$this->assertTrue($childroute->Valid($params));

		$usedparams = $childroute->GetUsedParameters();
		$this->assertCount(4, $usedparams);
		$this->assertContains('parent', $usedparams);
		$this->assertContains('child', $usedparams);
		$this->assertContains('parentparam', $usedparams);
		$this->assertContains('childparam', $usedparams);

		$routeparams = $childroute->Match('parent/parentvalue/child/childvalue');
		$this->assertCount(4, $routeparams);
		$this->assertEquals('parentvalue', $routeparams['parent']);
		$this->assertEquals('childvalue', $routeparams['child']);
		$this->assertEquals('parentvalue', $routeparams['parentparam']);
		$this->assertEquals('childvalue', $routeparams['childparam']);

		$this->assertEquals('parent/parentvalue/child/childvalue', $childroute->Build($params));
	}

}
