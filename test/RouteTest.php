<?php
/**
 * Route test class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Test
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

		$this->assertFalse($route->Match($params));

		$params['param2'] = 'badvalue';

		$this->assertFalse($route->Match($params));

		$params['param2'] = 'testvalue2';

		$this->assertTrue($route->Match($params));

		$params['param2'] = 'testvalue3';

		$this->assertTrue($route->Match($params));
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

	public function testExtraParams()
	{
		$route = new GitPHP_Route('test/:param1/route/:param2', array(), array('otherparam' => 'othervalue', 'otherparam2' => 'othervalue2'));
		$params = $route->GetExtraParameters();
		$this->assertCount(2, $params);

		$this->assertEquals('othervalue', $params['otherparam']);
		$this->assertEquals('othervalue2', $params['otherparam2']);
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

		$childroute = new GitPHP_Route('child/:child', array('child' => 'childvalue'), array('childparam' => 'childvalue'));

		$childroute->SetParent($parentroute);

		$this->assertEquals('parent/:parent/child/:child', $childroute->GetPath());

		$params = array(
			'child' => 'childvalue'
		);

		$this->assertFalse($childroute->Match($params));

		$params['parent'] = 'parentvalue';

		$this->assertTrue($childroute->Match($params));

		$usedparams = $childroute->GetUsedParameters();
		$this->assertCount(4, $usedparams);
		$this->assertContains('parent', $usedparams);
		$this->assertContains('child', $usedparams);
		$this->assertContains('parentparam', $usedparams);
		$this->assertContains('childparam', $usedparams);

		$routeparams = $childroute->GetExtraParameters();
		$this->assertCount(2, $routeparams);
		$this->assertEquals('parentvalue', $routeparams['parentparam']);
		$this->assertEquals('childvalue', $routeparams['childparam']);

		$this->assertEquals('parent/parentvalue/child/childvalue', $childroute->Build($params));
	}

}
