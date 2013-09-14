<?php
/**
 * Request router
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Router
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
	 * Base url
	 *
	 * @var string
	 */
	protected $baseurl;

	/**
	 * Full url
	 *
	 * @var string
	 */
	protected $fullurl;

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

		$this->baseurl = GitPHP_Util::BaseUrl();
		if (empty($this->baseurl))
			$this->baseurl = '/';
		$this->fullurl = GitPHP_Util::BaseUrl(true);

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
	 * Get base url
	 *
	 * @param boolean $full true to return full base url (include protocol and hostname)
	 * @return string base url
	 */
	public function GetBaseUrl($full = false)
	{
		if ($full)
			return $this->fullurl;

		return $this->baseurl;
	}

	/**
	 * Set base url
	 *
	 * @param string $baseurl base url
	 */
	public function SetBaseUrl($baseurl)
	{
		$this->baseurl = $baseurl;
		$this->fullurl = $baseurl;
	}

	/**
	 * Initialize route map
	 */
	private function InitializeRoutes()
	{
		// project view
		$projectroute = new GitPHP_Route('projects/:project', array(
			'project' => '[^\?]+'
		));

		// project-specific action with hash and output method
		$this->routes[] = new GitPHP_Route(':action/:hash/:output', array(
			'action' => 'blobs',
			'hash' => '[0-9A-Fa-f]{4,40}|HEAD',
			'output' => 'plain'
		), array(), $projectroute);

		// project-specific action with hash
		$this->routes[] = new GitPHP_Route(':action/:hash', array(
			'action' => 'commits|trees|blobs|search|snapshot|commitdiff|blobdiff|blame',
			'hash' => '[0-9A-Fa-f]{4,40}|HEAD'
		), array(), $projectroute);

		// project-specific action with hash or ref
		$this->routes[] = new GitPHP_Route(':action/:hash', array(
			'action' => 'shortlog|log',
			'hash' => '[^\?]+'
		), array(), $projectroute);

		// map heads to shortlog
		$this->routes[] = new GitPHP_Route(':action/:hash', array(
			'action' => 'heads',
			'hash' => '[^\?]+'
		), array(
			'action' => 'shortlog'
		), $projectroute);

		// project-specific graphs
		$this->routes[] = new GitPHP_Route(':action/:graphtype', array(
			'action' => 'graphs',
			'graphtype' => '[a-z]+'
		), array(), $projectroute);

		// project-specific tag
		$this->routes[] = new GitPHP_Route(':action/:tag', array(
			'action' => 'tags',
			'tag' => '[^\?]+'
		), array(), $projectroute);

		$formats = GitPHP_Archive::SupportedFormats();
		if (count($formats) > 0) {
			$formatconstraint = implode("|", array_keys($formats));
			// project specific snapshot format with hash
			$this->routes[] = new GitPHP_Route(':format/:hash', array(
				'format' => $formatconstraint,
				'hash' => '[0-9A-Fa-f]{4,40}|HEAD'
			), array(
				'action' => 'snapshot'
			), $projectroute);

			// project specific snapshot format
			$this->routes[] = new GitPHP_Route(':format', array(
				'format' => $formatconstraint
			), array(
				'action' => 'snapshot'
			), $projectroute);
		}

		// project-specific action only
		$this->routes[] = new GitPHP_Route(':action', array(
			'action' => 'tags|heads|shortlog|log|search|atom|rss|snapshot|commits|graphs|trees|blobs|history|commitdiff|blobdiff'
		), array(), $projectroute);

		$this->routes[] = $projectroute;

		// non-project action
		$this->routes[] = new GitPHP_Route(':action', array(
			'action' => 'opml|projectindex|login|logout'
		));

		usort($this->routes, array('GitPHP_Route', 'CompareRoute'));
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
			'lang' => 'l',
			'redirect' => 'redirect'
		);
	}

	/**
	 * Convert a parameter array to a query variable array
	 *
	 * @param array $params parameter array
	 * @return array query variable array
	 */
	private function ParameterArrayToQueryVarArray($params)
	{
		$queryvars = array();

		if (count($params) < 1)
			return $queryvars;

		foreach ($params as $param => $val) {

			if (empty($val))
				continue;

			if (empty($this->queryParameters[$param]))
				continue;

			$queryvar = $this->queryParameters[$param];

			if (!empty($queryvar))
				$queryvars[$queryvar] = $val;
		}

		return $queryvars;
	}

	/**
	 * Convert a query variable array to a parameter array
	 *
	 * @param array $queryvars query variable array
	 * @return array parameter array
	 */
	private function QueryVarArrayToParameterArray($queryvars)
	{
		$params = array();

		if (count($queryvars) < 1)
			return $params;

		foreach ($queryvars as $var => $val) {

			if (empty($val))
				continue;

			$param = array_search($var, $this->queryParameters);

			if (!empty($param))
				$params[$param] = $val;
		}

		return $params;
	}

	/**
	 * Build route from url parameters
	 *
	 * @param array $urlparams url parameters
	 */
	private function BuildRoute($urlparams)
	{
		foreach ($this->routes as $route) {

			if (!$route->Valid($urlparams))
				continue;

			$path = $route->Build($urlparams);
			$usedparams = $route->GetUsedParameters();

			return array($path, $usedparams);
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

		foreach ($this->routes as $route) {

			$params = $route->Match($query);
			if ($params === false)
				continue;

			return $params;
		}

		return array();
	}

	/**
	 * Gets a controller for an action
	 *
	 * @return GitPHP_ControllerBase
	 */
	public function GetController()
	{
		$params = $this->QueryVarArrayToParameterArray($_GET);
		if (!empty($_POST)) {
			$params = array_merge($params, $this->QueryVarArrayToParameterArray($_POST));
		}

		if (!empty($_GET['q'])) {
			$restparams = GitPHP_Router::ReadCleanUrl($_SERVER['REQUEST_URI']);
			if (count($restparams) > 0)
				$params = array_merge($params, $restparams);
		}

		$controller = null;

		$action = null;
		if (!empty($params['action']))
			$action = $params['action'];

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
				if (empty($params['tag'])) {
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


			case 'login':
				$controller = new GitPHP_Controller_Login();
				if (!empty($_POST['username']))
					$controller->SetParam('username', $_POST['username']);
				if (!empty($_POST['password']))
					$controller->SetParam('password', $_POST['password']);
				break;

			case 'logout':
				$controller = new GitPHP_Controller_Logout();
				break;


			case 'graph':
			case 'graphs':
				//$controller = new GitPHP_Controller_Graph();
				//break;
			case 'graphdata':
				//$controller = new GitPHP_Controller_GraphData();
				//break;
			default:
				if (!empty($params['project'])) {
					$controller = new GitPHP_Controller_Project();
				} else {
					$controller = new GitPHP_Controller_ProjectList();
				}
		}

		foreach ($params as $paramname => $paramval) {
			if ($paramname !== 'action')
				$controller->SetParam($paramname, $paramval);
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
		$params = $this->QueryVarArrayToParameterArray($_GET);

		if (!empty($_GET['q'])) {
			$restparams = GitPHP_Router::ReadCleanUrl($_SERVER['REQUEST_URI']);
			if (count($restparams) > 0)
				$params = array_merge($params, $restparams);
		}

		$controller = new GitPHP_Controller_Message();

		foreach ($params as $paramname => $paramval) {
			if ($paramname !== 'action')
				$controller->SetParam($paramname, $paramval);
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

		$baseurl = GitPHP_Util::AddSlash(GitPHP_Util::BaseUrl(), false);
		if (empty($baseurl))
			$baseurl = '/';

		if (strncmp($baseurl, $url, strlen($baseurl)) === 0)
			$url = substr($url, strlen($baseurl));

		return $this->FindRoute($url);
	}

	/**
	 * Generate a url
	 *
	 * @param array $params request parameters
	 * @param boolean $full true to get full url (include protocol and hostname)
	 */
	public function GetUrl($params = array(), $full = false)
	{
		if ($full)
			$baseurl = $this->fullurl;
		else
			$baseurl = $this->baseurl;

		if ($this->cleanurl) {
			if (substr_compare($baseurl, 'index.php', -9) === 0) {
				$baseurl = dirname($baseurl);
			}
			$baseurl = GitPHP_Util::AddSlash($baseurl, false);
		} else {
			if (substr_compare($baseurl, 'index.php', -9) !== 0) {
				$baseurl = GitPHP_Util::AddSlash($baseurl, false);
			}
		}

		if (count($params) < 1)
			return $baseurl;

		$abbreviate = $this->abbreviate;
		if ($abbreviate && !empty($params['project']) && ($params['project'] instanceof GitPHP_Project)) {
			if ($params['project']->GetCompat())
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

			if (!empty($params['action'])) {
				switch ($params['action']) {
					case 'blob':
					case 'commit':
					case 'tree':
					case 'graph':
					case 'tag':
						// these actions are plural in clean urls
						$params['action'] = $params['action'] . 's';
						break;
				}
			}

			list($queryurl, $exclude) = $this->BuildRoute($params);
			$baseurl .= $queryurl;

			foreach ($exclude as $excludeparam) {
				unset($params[$excludeparam]);
			}
		}

		$querystr = GitPHP_Router::GetQueryString($params);

		if (empty($querystr))
			return $baseurl;

		return $baseurl . '?' . $querystr;
	}

	/**
	 * Gets query parameters for a url
	 *
	 * @param array $params query parameters
	 * @return string query string
	 */
	private function GetQueryString($params = array())
	{
		if (count($params) < 1)
			return null;

		$query = $this->ParameterArrayToQueryVarArray($params);

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
		if ($value instanceof GitPHP_GitObject)
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
		} else if (is_string($value)) {
			return $value;
		}
	}

}
