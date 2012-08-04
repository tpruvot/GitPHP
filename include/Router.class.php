<?php
/**
 * Request router
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Router
{

	/**
	 * Route map
	 *
	 * @var array
	 */
	protected $routes;

	/**
	 * Clean url flag
	 *
	 * @var boolean
	 */
	protected $cleanurl = false;

	/**
	 * Constructor
	 *
	 * @param boolean $cleanurl true to generate clean urls
	 */
	public function __construct($cleanurl = false)
	{
		$this->cleanurl = $cleanurl;
		$this->InitializeRoutes();
	}

	/**
	 * Get clean url setting
	 *
	 * @return boolean
	 */
	public function GetCleanUrl()
	{
		return $this->cleanurl;
	}

	/**
	 * Set clean url setting
	 *
	 * @param boolean $cleanurl true to generate clean urls
	 */
	public function SetCleanUrl($cleanurl)
	{
		$this->cleanurl = $cleanurl;
	}

	/**
	 * Initialize route map
	 */
	private function InitializeRoutes()
	{
		// project view
		$projectroute = array(
			'path' => 'projects/:project',
			'constraints' => array(
				'project' => '/^[^\/\?]+$/'
			),
			'transforms' => array(
				'project' => array('GitPHP_Router', 'GetProject')
			)
		);

		// project-specific plural action with hash
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^commits|trees|blobs$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/'
			),
			'transforms' => array(
				'hash' => array('GitPHP_Router', 'GetHash'),
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific singular action with hash or ref
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^shortlog|log$/',
				'hash' => '/^[^\/\?]+$/'
			),
			'transforms' => array(
				'hash' => array('GitPHP_Router', 'GetHash'),
			)
		));

		// project-specific singular action with hash only
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^search$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/'
			),
			'transforms' => array(
				'hash' => array('GitPHP_Router', 'GetHash'),
			)
		));

		// project-specific plural action only
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action',
			'constraints' => array(
				'action' => '/^commits|graphs|trees$/'
			),
			'transforms' => array(
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific graphs
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:graphtype',
			'constraints' => array(
				'action' => '/^graphs$/',
				'graphtype' => '/^[a-z]+$/'
			),
			'transforms' => array(
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific singular action only
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action',
			'constraints' => array(
				'action' => '/^tags|heads|shortlog|log|search|atom|rss$/'
			)
		));

		// project-specific tag
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^tags$/',
				'hash' => '/^[^\/\?]+$/'
			),
			'transforms' => array(
				'hash' => array('GitPHP_Router', 'GetTag'),
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific plural action with hash and output method
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash/:output',
			'constraints' => array(
				'action' => '/^blobs$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/',
				'output' => '/^plain$/'
			),
			'transforms' => array(
				'hash' => array('GitPHP_Router', 'GetHash'),
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		$this->routes[] = $projectroute;

		// non-project action
		$this->routes[] = array(
			'path' => ':action',
			'constraints' => array(
				'action' => '/^opml|projectindex$/'
			)
		);

	}

	/**
	 * Embed a route beneath another route
	 *
	 * @param array $parent parent route
	 * @param array $child child route
	 * @return array embedded route
	 */
	private static function EmbedRoute($parent, $child)
	{
		$finalroute = array();

		$finalroute['path'] = $parent['path'] . '/' . $child['path'];
		$finalroute['constraints'] = array_merge($parent['constraints'], $child['constraints']);
		if ((!empty($parent['transforms']) && is_array($parent['transforms'])) && (!empty($child['transforms']) && is_array($child['transforms'])))
			$finalroute['transforms'] = array_merge($parent['transforms'], $child['transforms']);
		else if (!empty($parent['transforms']) && is_array($parent['transforms']))
			$finalroute['transforms'] = $parent['transforms'];
		else if (!empty($child['transforms']) && is_array($child['transforms']))
			$finalroute['transforms'] = $child['transforms'];

		return $finalroute;
	}

	/**
	 * Convert a parameter to a query parameter
	 *
	 * @param string $param parameter
	 * @return string query parameter
	 */
	private static function GetQueryParameter($param)
	{
		$queryparams = array(
			'project' => 'p',
			'action' => 'a',
			'hash' => 'h',
			'graphtype' => 'g',
			'output' => 'o'
		);
		if (!empty($queryparams[$param]))
			return $queryparams[$param];
	
		return null;
	}

	/**
	 * Build route from url parameters
	 *
	 * @param array $urlparams url parameters
	 */
	public function BuildRoute($urlparams)
	{
		foreach ($this->routes as $route) {
			$routepieces = explode("/", $route['path']);

			$match = true;
			$paramnames = array();
			foreach ($routepieces as $i => $piece) {
				if (strncmp($piece, ':', 1) !== 0) {
					// not a param
					continue;
				}

				$paramname = substr($piece, 1);
				$paramnames[] = $paramname;

				if (empty($urlparams[$paramname])) {
					// missing a required param
					$match = false;
					break;
				}

				$paramval = $urlparams[$paramname];

				if (!empty($paramval) && !empty($route['transforms'][$paramname])) {
					$paramval = call_user_func($route['transforms'][$paramname], $paramval);
				}

				if (!empty($route['constraints'][$paramname])) {
					if (!preg_match($route['constraints'][$paramname], $paramval)) {
						// param doesn't match constraint
						$match = false;
						break;
					}
				}

				$routepieces[$i] = $paramval;

			}

			if (!$match)
				continue;

			return array(trim(implode("/", $routepieces), "/"), $paramnames);
		}

		return array(null, array());
	}

	/**
	 * Find route matching query
	 *
	 * @param string $query query
	 * @return array query parameters
	 */
	public function FindRoute($query)
	{
		if (empty($query))
			return array();

		$querypieces = explode("/", $query);

		foreach ($this->routes as $route) {
			$routepieces = explode("/", $route['path']);

			if (count($querypieces) != count($routepieces))
				continue;

			$match = true;
			$params = array();
			for ($i = 0; $i < count($querypieces); ++$i) {
				$routepiece = $routepieces[$i];
				$querypiece = $querypieces[$i];
				if (strncmp($routepiece, ':', 1) === 0) {
					// parameter
					$routepiece = substr($routepiece, 1);
					if (!preg_match($route['constraints'][$routepiece], $querypiece)) {
						$match = false;
						break;
					}

					$queryparam = GitPHP_Router::GetQueryParameter($routepiece);
					if (!empty($queryparam)) {
						$params[$queryparam] = rawurldecode($querypiece);
					}
				} else {
					// literal string
					if ($querypieces[$i] != $routepiece) {
						$match = false;
						break;
					}
				}
			}
			if (!$match)
				continue;

			return $params;
		}
	}

	/**
	 * Gets a controller for an action
	 *
	 * @return mixed controller object
	 */
	public function GetController()
	{
		$query = $_GET;

		if (!empty($query['q'])) {
			$restquery = GitPHP_Router::ReadCleanUrl($_SERVER['REQUEST_URI']);
			if (count($restquery) > 0)
				$query = array_merge($query, $restquery);
		}

		$action = null;
		if (isset($query['a']))
			$action = $query['a'];

		$controller = null;

		switch ($action) {


			case 'search':
				$controller = new GitPHP_Controller_Search();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['pg']))
					$controller->SetParam('page', $query['pg']);
				break;


			case 'commitdiff':
			case 'commitdiff_plain':
				$controller = new GitPHP_Controller_Commitdiff();
				if ($action === 'commitdiff_plain')
					$controller->SetParam('output', 'plain');
				else if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				if (!empty($query['d'])) {
					$controller->SetParam('diffmode', $query['d']);
				}
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['hp']))
					$controller->SetParam('hashparent', $query['hp']);
				break;


			case 'blobdiff':
			case 'blobdiff_plain':
				$controller = new GitPHP_Controller_Blobdiff();
				if ($action === 'blobdiff_plain')
					$controller->SetParam('output', 'plain');
				else if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				if (!empty($query['d'])) {
					$controller->SetParam('diffmode', $query['d']);
				}
				if (!empty($query['f']))
					$controller->SetParam('file', $query['f']);
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['hb']))
					$controller->SetParam('hashbase', $query['hb']);
				if (!empty($query['hp']))
					$controller->SetParam('hashparent', $query['hp']);
				break;


			case 'history':
				$controller = new GitPHP_Controller_History();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['f']))
					$controller->SetParam('file', $query['f']);
				break;


			case 'shortlog':
			case 'log':
				$controller = new GitPHP_Controller_Log();
				if ($action === 'shortlog')
					$controller->SetParam('short', true);
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['pg']))
					$controller->SetParam('page', $query['pg']);
				if (!empty($query['m']))
					$controller->SetParam('mark', $query['m']);
				break;


			case 'snapshot':
				$controller = new GitPHP_Controller_Snapshot();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['f']))
					$controller->SetParam('path', $query['f']);
				if (!empty($query['prefix']))
					$controller->SetParam('prefix', $query['prefix']);
				if (!empty($query['fmt']))
					$controller->SetParam('format', $query['fmt']);
				break;


			case 'tree':
			case 'trees':
				$controller = new GitPHP_Controller_Tree();
				if (!empty($query['f']))
					$controller->SetParam('file', $query['f']);
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['hb']))
					$controller->SetParam('hashbase', $query['hb']);
				if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				break;


			case 'tags':
				if (empty($query['h'])) {
					$controller = new GitPHP_Controller_Tags();
					break;
				}
			case 'tag':
				$controller = new GitPHP_Controller_Tag();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				break;


			case 'heads':
				$controller = new GitPHP_Controller_Heads();
				break;


			case 'blame':
				$controller = new GitPHP_Controller_Blame();
				if (!empty($query['hb']))
					$controller->SetParam('hashbase', $query['hb']);
				if (!empty($query['f']))
					$controller->SetParam('file', $query['f']);
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['o'])) {
					$controller->SetParam('output', $query['o']);
				}
				break;


			case 'blob':
			case 'blobs':
			case 'blob_plain':	
				$controller = new GitPHP_Controller_Blob();
				if ($action === 'blob_plain')
					$controller->SetParam('output', 'plain');
				else if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				if (!empty($query['hb']))
					$controller->SetParam('hashbase', $query['hb']);
				if (!empty($query['f']))
					$controller->SetParam('file', $query['f']);
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				break;


			case 'atom':
			case 'rss':
				$controller = new GitPHP_Controller_Feed();
				if ($action == 'rss')
					$controller->SetParam('format', GitPHP_Controller_Feed::RssFormat);
				else if ($action == 'atom')
					$controller->SetParam('format', GitPHP_Controller_Feed::AtomFormat);
				break;


			case 'commit':
			case 'commits':
				$controller = new GitPHP_Controller_Commit();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				break;


			case 'summary':
				$controller = new GitPHP_Controller_Project();
				break;


			case 'project_index':
			case 'projectindex':
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('txt', true);
				break;


			case 'opml':
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('opml', true);
				break;


			case 'graph':
			case 'graphs':
				$controller = new GitPHP_Controller_Graph();
				if (!empty($query['g']))
					$controller->SetParam('graphtype', $query['g']);
				break;


			case 'graphdata':
				$controller = new GitPHP_Controller_GraphData();
				if (!empty($query['g']))
					$controller->SetParam('graphtype', $query['g']);
				break;


			default:
				if (!empty($query['p'])) {
					$controller = new GitPHP_Controller_Project();
				} else {
					$controller = new GitPHP_Controller_ProjectList();
					if (!empty($query['o'])) {
						$controller->SetParam('order', $query['o']);
					}
				}
		}

		if (!empty($query['p'])) {
			$controller->SetParam('project', $query['p']);
		}

		if (!empty($query['s'])) {
			$controller->SetParam('search', $query['s']);
		}

		if (!empty($query['st'])) {
			$controller->SetParam('searchtype', $query['st']);
		}

		if (!empty($query['l'])) {
			$controller->SetParam('lang', $query['l']);
		}

		return $controller;
	}

	/**
	 * Read a rest-style clean url
	 *
	 * @param string $url url
	 * @return array request parameters from url
	 */
	private function ReadCleanUrl($url)
	{
		$querypos = strpos($url, '?');
		if ($querypos !== false)
			$url = substr($url, 0, $querypos);

		$url = rtrim($url, "/");

		$baseurl = $_SERVER['SCRIPT_NAME'];
		if (substr_compare($baseurl, '.php', -4) === 0)
			$baseurl = dirname($baseurl);
		$baseurl = GitPHP_Util::AddSlash($baseurl);

		if (strncmp($baseurl, $url, strlen($baseurl)) === 0)
			$url = substr($url, strlen($baseurl));

		$params = $this->FindRoute($url);

		return $params;
	}

	/**
	 * Generate a url
	 *
	 * @param string $baseurl base request url
	 * @param array $params request parameters
	 * @param boolean $abbreviate true to abbreviate url hashes
	 */
	public function GetUrl($baseurl, $params = array(), $abbreviate = false)
	{
		if (count($params) < 1)
			return $baseurl;

		$exclude = array();

		if ($this->cleanurl) {
			if (substr_compare($baseurl, '.php', -4) === 0) {
				$baseurl = dirname($baseurl);
			}
			$baseurl = GitPHP_Util::AddSlash($baseurl);

			if (count($params) < 1)
				return $baseurl;

			list($queryurl, $exclude) = $this->BuildRoute($params);
			$baseurl .= $queryurl;
		}

		$querystr = GitPHP_Router::GetQueryParameters($params, $abbreviate, $exclude);

		if (empty($querystr))
			return $baseurl;

		return $baseurl . '?' . $querystr;
	}

	/**
	 * Gets query parameters for a url
	 *
	 * @param array $params query parameters
	 * @param boolean $abbreviate true to abbreviate url hashes
	 * @param string[] $exclude array of parameter names to exclude
	 * @return string query string
	 */
	private function GetQueryParameters($params = array(), $abbreviate = false, $exclude = array())
	{
		if (count($params) < 1)
			return null;

		$query = array();

		if (!empty($params['project'])) {
			if ($params['project'] instanceof GitPHP_Project) {
				if (!in_array('project', $exclude)) {
					$query['p'] = rawurlencode($params['project']->GetProject());
				}
				if ($abbreviate && $params['project']->GetCompat())
					$abbreviate = false;
			} else if (is_string($params['project'])) {
				if (!in_array('project', $exclude)) {
					$query['p'] = rawurlencode($params['project']);
				}
			}
		}

		$action = null;
		if (!empty($params['action'])) {
			$action = $params['action'];
			if (!in_array('action', $exclude)) {
				$query['a'] = $action;
			}
		}

		switch ($action) {
			case 'search':
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['page']))
					$query['pg'] = $params['page'];
				if (!empty($params['search']))
					$query['s'] = rawurlencode($params['search']);
				if (!empty($params['searchtype']))
					$query['st'] = $params['searchtype'];
				break;


			case 'commitdiff':
			case 'commitdiff_plain':
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['hashparent']))
					$query['hp'] = GitPHP_Router::GetHash($params['hashparent'], $abbreviate);
				if (!empty($params['diffmode']))
					$query['d'] = $params['diffmode'];
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'blobdiff':
			case 'blobdiff_plain':
				if (!empty($params['diffmode']))
					$query['d'] = $params['diffmode'];
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['hashbase']))
					$query['hb'] = GitPHP_Router::GetHash($params['hashbase'], $abbreviate);
				if (!empty($params['hashparent']))
					$query['hp'] = GitPHP_Router::GetHash($params['hashparent'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'history':
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				break;


			case 'shortlog':
			case 'log':
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['page']))
					$query['pg'] = $params['page'];
				if (!empty($params['mark']))
					$query['m'] = GitPHP_Router::GetHash($params['mark'], $abbreviate);
				break;


			case 'snapshot':
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['path']))
					$query['f'] = rawurlencode($params['path']);
				if (!empty($params['prefix']))
					$query['prefix'] = $params['prefix'];
				if (!(empty($params['format']) || in_array('format', $exclude)))
					$query['fmt'] = $params['format'];
				break;


			case 'tree':
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['hashbase']))
					$query['hb'] = GitPHP_Router::GetHash($params['hashbase'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'tag':
				if (!(empty($params['hash']) || in_array('hash', $exclude))) {
					if ($params['hash'] instanceof GitPHP_Tag) {
						$query['h'] = rawurlencode($params['hash']->GetName());
					} else if (is_string($params['hash'])) {
						$query['h'] = rawurlencode($params['hash']);
					}
				}
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'blame':
				if (!empty($params['hashbase']))
					$query['hb'] = GitPHP_Router::GetHash($params['hashbase'], $abbreviate);
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'blob':
			case 'blob_plain':
				if (!empty($params['hashbase']))
					$query['hb'] = GitPHP_Router::GetHash($params['hashbase'], $abbreviate);
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!(empty($params['output']) || in_array('output', $exclude)))
					$query['o'] = $params['output'];
				break;


			case 'commit':
				if (!(empty($params['hash']) || in_array('hash', $exclude)))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'graph':
				if (!(empty($params['graphtype']) || in_array('graphtype', $exclude)))
					$query['g'] = $params['graphtype'];
				break;


			case 'graphdata':
				if (!empty($params['graphtype']))
					$query['g'] = $params['graphtype'];
				break;


			default:
				if (empty($params['project'])) {
					if (!empty($params['order']))
						$query['o'] = $params['order'];
				}
		}

		if (count($query) < 1)
			return null;

		$querystr = null;

		foreach ($query as $var => $val) {
			if (empty($val))
				continue;
			if (!empty($querystr))
				$querystr .= '&';
			$querystr .= $var . '=' . $val;
		}

		return $querystr;
	}

	/**
	 * Gets a hash for a string or hash-identified object
	 *
	 * @param string|GitPHP_GitObject $value string or hashed object
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string hash
	 */
	private static function GetHash($value, $abbreviate = false)
	{
		if ($value instanceof GitPHP_Ref)
			return rawurlencode($value->GetRefPath());
		else if ($value instanceof GitPHP_GitObject)
			return $value->GetHash($abbreviate);
		else if (is_string($value))
			return $value;

		return null;
	}

	/**
	 * Gets an identifier for a tag
	 *
	 * @param string|GitPHP_Tag $value string or tag
	 * @return string hash
	 */
	private static function GetTag($value)
	{
		if ($value instanceof GitPHP_Tag)
			return rawurlencode($value->GetName());
		else if (is_string($value))
			return rawurlencode($value);
		return null;
	}

	/**
	 * Gets a project identifier for a project
	 *
	 * @param string|GitPHP_Project $value string or project
	 * @return string identifier
	 */
	private static function GetProject($value)
	{
		if ($value instanceof GitPHP_Project) {
			return rawurlencode($value->GetProject());
		} else if (is_string($project)) {
			return rawurlencode($value);
		}
	}

	/**
	 * Pluralize an action
	 *
	 * @param string $action singular action
	 * @return string plural action
	 */
	private static function Pluralize($action)
	{
		if (substr_compare($action, 's', -1) === 0)
			return $action;
		return $action . 's';
	}

}
