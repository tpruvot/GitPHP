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
	protected $routes = array();

	/**
	 * Query parameter map
	 *
	 * @var array
	 */
	protected $queryParameters = array();

	/**
	 * Clean url flag
	 *
	 * @var boolean
	 */
	protected $cleanurl = false;

	/**
	 * Abbreviate hashes flag
	 *
	 * @var boolean
	 */
	protected $abbreviate = false;

	/**
	 * Constructor
	 *
	 * @param boolean $cleanurl true to generate clean urls
	 * @param boolean $abbreviate true to abbreviate hashes
	 */
	public function __construct($cleanurl = false, $abbreviate = false)
	{
		$this->cleanurl = $cleanurl;
		$this->abbreviate = $abbreviate;
		$this->InitializeRoutes();
		$this->InitializeQueryParameters();
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
	 * Get abbreviate hash setting
	 *
	 * @return boolean
	 */
	public function GetAbbreviate()
	{
		return $this->abbreviate;
	}

	/**
	 * Set abbreviate hash setting
	 *
	 * @param boolean $abbreviate abbreviate
	 */
	public function SetAbbreviate($abbreviate)
	{
		$this->abbreviate = $abbreviate;
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
			)
		);

		// project-specific plural action with hash and output method
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash/:output',
			'constraints' => array(
				'action' => '/^blobs$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/',
				'output' => '/^plain$/'
			),
			'transforms' => array(
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific plural action with hash
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^commits|trees|blobs$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/'
			),
			'transforms' => array(
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		// project-specific singular action with hash or ref
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^shortlog|log$/',
				'hash' => '/^[^\/\?]+$/'
			)
		));

		// project-specific singular action with hash only
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:hash',
			'constraints' => array(
				'action' => '/^search|snapshot$/',
				'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/'
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

		// project-specific tag
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action/:tag',
			'constraints' => array(
				'action' => '/^tags$/',
				'tag' => '/^[^\/\?]+$/'
			),
			'transforms' => array(
				'action' => array('GitPHP_Router', 'Pluralize')
			)
		));

		$formats = GitPHP_Archive::SupportedFormats();
		if (count($formats) > 0) {
			$formatconstraint = '/^' . implode("|", array_keys($formats)) . '$/';
			// project specific snapshot format with hash
			$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
				'path' => ':format/:hash',
				'constraints' => array(
					'format' => $formatconstraint,
					'hash' => '/^([0-9A-Fa-f]{4,40}|HEAD)$/'
				),
				'params' => array(
					'action' => 'snapshot'
				)
			));

			// project specific snapshot format
			$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
				'path' => ':format',
				'constraints' => array(
					'format' => $formatconstraint
				),
				'params' => array(
					'action' => 'snapshot'
				)
			));
		}

		// project-specific singular action only
		$this->routes[] = GitPHP_Router::EmbedRoute($projectroute, array(
			'path' => ':action',
			'constraints' => array(
				'action' => '/^tags|heads|shortlog|log|search|atom|rss|snapshot$/'
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

		usort($this->routes, array('GitPHP_Router', 'CompareRoutes'));
	}

	/**
	 * Initialize query parameter map
	 */
	private function InitializeQueryParameters()
	{
		$this->queryParameters = array(
			'project' => 'p',
			'action' => 'a',
			'hash' => 'h',
			'hashbase' => 'hb',
			'hashparent' => 'hp',
			'graphtype' => 'g',
			'output' => 'o',
			'format' => 'fmt',
			'tag' => 't',
			'page' => 'pg',
			'search' => 's',
			'searchtype' => 'st',
			'diffmode' => 'd',
			'file' => 'f',
			'mark' => 'm',
			'prefix' => 'prefix',
			'sort' => 'sort',
			'lang' => 'l'
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
		if ((!empty($parent['params']) && is_array($parent['params'])) && (!empty($child['params']) && is_array($child['params'])))
			$finalroute['params'] = array_merge($parent['params'], $child['params']);
		else if (!empty($parent['params']) && is_array($parent['params']))
			$finalroute['params'] = $parent['params'];
		else if (!empty($child['params']) && is_array($child['params']))
			$finalroute['params'] = $child['params'];


		return $finalroute;
	}

	/**
	 * Convert a parameter to a query parameter
	 *
	 * @param string $param parameter
	 * @return string query parameter
	 */
	private function ParameterToQueryVar($param)
	{
		if (empty($param))
			return null;

		if (!empty($this->queryParameters[$param]))
			return $this->queryParameters[$param];
	
		return null;
	}

	/**
	 * Convert a query parameter to a parameter
	 *
	 * @param string $queryvar query variable
	 * @return string parameter
	 */
	private function QueryVarToParameter($queryvar)
	{
		if (empty($queryvar))
			return null;

		$parameter = array_search($queryvar, $this->queryParameters);

		if ($parameter !== false)
			return $parameter;

		return null;
	}

	/**
	 * Build route from url parameters
	 *
	 * @param array $urlparams url parameters
	 */
	private function BuildRoute($urlparams)
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
					$transform = $route['transforms'][$paramname];
					$paramval = call_user_func($transform, $paramval);
				}

				$paramval = rawurlencode($paramval);

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
	private function FindRoute($query)
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

					$queryparam = $this->ParameterToQueryVar($routepiece);
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

			if (!empty($route['params'])) {
				foreach ($route['params'] as $paramname => $paramval) {
					$queryparam = $this->ParameterToQueryVar($paramname);
					if (!empty($queryparam))
						$params[$queryparam] = $paramval;
				}
			}

			return $params;
		}
	}

	/**
	 * Route comparison function
	 *
	 * @param array $a route a
	 * @param array $b route b
	 * @return int comparison result
	 */
	private static function CompareRoutes($a, $b)
	{
		$acount = substr_count($a['path'], ':');
		$bcount = substr_count($b['path'], ':');

		if ($acount == $bcount) {
			$acount2 = substr_count($a['path'], '/');
			$bcount2 = substr_count($b['path'], '/');
			if ($acount2 == $bcount2)
				return 0;

			return $acount2 < $bcount2 ? 1 : -1;
		}

		return $acount < $bcount ? 1 : -1;
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
				break;


			case 'commitdiff':
			case 'commitdiff_plain':
				$controller = new GitPHP_Controller_Commitdiff();
				if ($action === 'commitdiff_plain')
					$controller->SetParam('output', 'plain');
				break;


			case 'blobdiff':
			case 'blobdiff_plain':
				$controller = new GitPHP_Controller_Blobdiff();
				if ($action === 'blobdiff_plain')
					$controller->SetParam('output', 'plain');
				break;


			case 'history':
				$controller = new GitPHP_Controller_History();
				break;


			case 'shortlog':
			case 'log':
				$controller = new GitPHP_Controller_Log();
				if ($action === 'shortlog')
					$controller->SetParam('short', true);
				break;


			case 'snapshot':
				$controller = new GitPHP_Controller_Snapshot();
				break;


			case 'tree':
			case 'trees':
				$controller = new GitPHP_Controller_Tree();
				break;


			case 'tags':
				if (empty($query['t'])) {
					$controller = new GitPHP_Controller_Tags();
					break;
				}
			case 'tag':
				$controller = new GitPHP_Controller_Tag();
				break;


			case 'heads':
				$controller = new GitPHP_Controller_Heads();
				break;


			case 'blame':
				$controller = new GitPHP_Controller_Blame();
				break;


			case 'blob':
			case 'blobs':
			case 'blob_plain':	
				$controller = new GitPHP_Controller_Blob();
				if ($action === 'blob_plain')
					$controller->SetParam('output', 'plain');
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
				break;


			case 'graphdata':
				$controller = new GitPHP_Controller_GraphData();
				break;


			default:
				if (!empty($query['p'])) {
					$controller = new GitPHP_Controller_Project();
				} else {
					$controller = new GitPHP_Controller_ProjectList();
				}
		}

		foreach ($query as $queryparam => $queryval) {
			if (empty($queryval))
				continue;

			if (($queryparam == 'a') || ($queryparam == 'q'))
				continue;

			$paramname = $this->QueryVarToParameter($queryparam);
			if (empty($paramname))
				continue;

			$controller->SetParam($paramname, $queryval);
		}

		$controller->SetRouter($this);

		return $controller;
	}

	/**
	 * Get message controller
	 *
	 * @return GitPHP_ControllerBase
	 */
	public function GetMessageController()
	{
		$query = $_GET;

		if (!empty($query['q'])) {
			$restquery = GitPHP_Router::ReadCleanUrl($_SERVER['REQUEST_URI']);
			if (count($restquery) > 0)
				$query = array_merge($query, $restquery);
		}

		$controller = new GitPHP_Controller_Message();

		foreach ($query as $queryparam => $queryval) {
			if (empty($queryval))
				continue;

			if (($queryparam == 'a') || ($queryparam == 'q'))
				continue;

			$paramname = $this->QueryVarToParameter($queryparam);
			if (empty($paramname))
				continue;

			$controller->SetParam($paramname, $queryval);
		}

		$controller->SetRouter($this);

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
	 */
	public function GetUrl($baseurl, $params = array())
	{
		if (count($params) < 1)
			return $baseurl;

		$exclude = array();

		$abbreviate = $this->abbreviate;

		if (!empty($params['project']) && ($params['project'] instanceof GitPHP_Project)) {
			if ($abbreviate && $params['project']->GetCompat())
				$abbreviate = false;
		}

		foreach ($params as $paramname => $paramval) {
			switch ($paramname) {
				case 'hash':
				case 'hashbase':
				case 'hashparent':
				case 'mark':
					$params[$paramname] = GitPHP_Router::GetHash($paramval, $abbreviate);
					break;
				case 'tag':
					$params[$paramname] = GitPHP_Router::GetTag($paramval);
					break;
				case 'project':
					$params[$paramname] = GitPHP_Router::GetProject($paramval);
					break;
			}
		}

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

		$querystr = GitPHP_Router::GetQueryString($params, $exclude);

		if (empty($querystr))
			return $baseurl;

		return $baseurl . '?' . $querystr;
	}

	/**
	 * Gets query parameters for a url
	 *
	 * @param array $params query parameters
	 * @param string[] $exclude array of parameter names to exclude
	 * @return string query string
	 */
	private function GetQueryString($params = array(), $exclude = array())
	{
		if (count($params) < 1)
			return null;

		$query = array();

		foreach ($params as $paramname => $paramval) {
			if (empty($paramval))
				continue;

			if (in_array($paramname, $exclude))
				continue;

			$queryvar = $this->ParameterToQueryVar($paramname);
			if (empty($queryvar))
				continue;

			$query[$queryvar] = $paramval;
		}

		if (count($query) < 1)
			return null;

		$querystr = null;

		foreach ($query as $var => $val) {
			if (empty($val))
				continue;
			if (!empty($querystr))
				$querystr .= '&';
			$querystr .= $var . '=' . rawurlencode($val);
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
			return $value->GetRefPath();
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
			return $value->GetName();
		else if (is_string($value))
			return $value;
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
			return $value->GetProject();
		} else if (is_string($project)) {
			return $value;
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
