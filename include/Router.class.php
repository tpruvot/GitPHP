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
	 * Gets a controller for an action
	 *
	 * @return mixed controller object
	 */
	public static function GetController()
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


			case 'tag':
				$controller = new GitPHP_Controller_Tag();
				if (!empty($query['h']))
					$controller->SetParam('hash', $query['h']);
				if (!empty($query['o']))
					$controller->SetParam('output', $query['o']);
				break;


			case 'tags':
				$controller = new GitPHP_Controller_Tags();
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
	private static function ReadCleanUrl($url)
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


		$params = array();

		if (preg_match('@^project/([^/\?]+)/commit/([0-9A-Fa-f]{4,40}|HEAD)$@', $url, $regs)) {
			$params['p'] = rawurldecode($regs[1]);
			$params['a'] = 'commit';
			$params['h'] = $regs[2];
		}

		if (preg_match('@^project/([^/\?]+)/tag/([^/\?]+)$@', $url, $regs)) {
			$params['p'] = rawurldecode($regs[1]);
			$params['a'] = 'tag';
			$params['h'] = $regs[2];
		}

		if (preg_match('@^project/([^/\?]+)/atom$@', $url, $regs)) {
			$params['p'] = rawurldecode($regs[1]);
			$params['a'] = 'atom';
		}

		if (preg_match('@^project/([^/\?]+)/rss$@', $url, $regs)) {
			$params['p'] = rawurldecode($regs[1]);
			$params['a'] = 'rss';
		}

		if (preg_match('@^project/([^/\?]+)$@', $url, $regs)) {
			$params['p'] = rawurldecode($regs[1]);
		}

		if (preg_match('@^opml$@', $url)) {
			$params['a'] = 'opml';
		}

		if (preg_match('@^projectindex$@', $url)) {
			$params['a'] = 'projectindex';
		}

		return $params;
	}

	/**
	 * Generate a url
	 *
	 * @param string $baseurl base request url
	 * @param array $params request parameters
	 * @param boolean $abbreviate true to abbreviate url hashes
	 */
	public static function GetUrl($baseurl, $params = array(), $abbreviate = false)
	{
		if (count($params) < 1)
			return $baseurl;

		$querystr = GitPHP_Router::GetQueryParameters($params, $abbreviate);

		if (empty($querystr))
			return $baseurl;

		return $baseurl . '?' . $querystr;
	}

	/**
	 * Generate a clean url
	 *
	 * @param string $baseurl base request url
	 * @param array $params request parameters
	 * @param boolean $abbreviate true to abbreviate url hashes
	 */
	public static function GetCleanUrl($baseurl, $params = array(), $abbreviate = false)
	{
		if (substr_compare($baseurl, '.php', -4) === 0) {
			$baseurl = dirname($baseurl);
		}
		$baseurl = GitPHP_Util::AddSlash($baseurl);

		if (count($params) < 1)
			return $baseurl;

		$exclude = array();

		if (!empty($params['project'])) {
			if ($params['project'] instanceof GitPHP_Project) {
				$baseurl .= 'project/' . rawurlencode($params['project']->GetProject());
				if ($abbreviate && $params['project']->GetCompat())
					$abbreviate = false;
				$exclude[] = 'project';
			} else if (is_string($params['project'])) {
				$baseurl .= 'project/' . rawurlencode($params['project']);
				$exclude[] = 'project';
			}
		}

		if (!empty($params['action'])) {
			switch ($params['action']) {

				case 'opml':
					$baseurl .= 'opml';
					$exclude[] = 'action';
					break;

				case 'projectindex':
					$baseurl .= 'projectindex';
					$exclude[] = 'action';
					break;

				case 'atom':
					$baseurl .= '/atom';
					$exclude[] = 'action';
					break;

				case 'rss':
					$baseurl .= '/rss';
					$exclude[] = 'action';
					break;

				case 'commit':
					$baseurl .= '/commit/' . GitPHP_Router::GetHash($params['hash'], $abbreviate);
					$exclude[] = 'action';
					$exclude[] = 'hash';
					break;

				case 'tag':
					$baseurl .= '/tag/';
					if ($params['hash'] instanceof GitPHP_Tag) {
						$baseurl .= rawurlencode($params['hash']->GetName());
					} else if (is_string($params['hash'])) {
						$baseurl .= rawurlencode($params['hash']);
					}
					$exclude[] = 'action';
					$exclude[] = 'hash';
					break;
			}
		}

		$querystr = GitPHP_Router::GetQueryParameters($params, $abbreviate, $exclude);
		if (!empty($querystr))
			$baseurl = GitPHP_Util::AddSlash($baseurl) . '?' . $querystr;

		return $baseurl;
	}

	/**
	 * Gets query parameters for a url
	 *
	 * @param array $params query parameters
	 * @param boolean $abbreviate true to abbreviate url hashes
	 * @param string[] $exclude array of parameter names to exclude
	 * @return string query string
	 */
	private static function GetQueryParameters($params = array(), $abbreviate = false, $exclude = array())
	{
		if (count($params) < 1)
			return null;

		$query = array();

		if (!(empty($params['project']) || in_array('project', $exclude))) {
			if ($params['project'] instanceof GitPHP_Project) {
				$query['p'] = rawurlencode($params['project']->GetProject());
				if ($abbreviate && $params['project']->GetCompat())
					$abbreviate = false;
			} else if (is_string($params['project'])) {
				$query['p'] = rawurlencode($params['project']);
			}
		}

		$action = null;
		if (!(empty($params['action']) || in_array('action', $exclude))) {
			$action = $params['action'];
			$query['a'] = $action;
		}

		switch ($action) {
			case 'search':
				if (!empty($params['hash']))
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
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['page']))
					$query['pg'] = $params['page'];
				if (!empty($params['mark']))
					$query['m'] = GitPHP_Router::GetHash($params['mark'], $abbreviate);
				break;


			case 'snapshot':
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['path']))
					$query['f'] = rawurlencode($params['path']);
				if (!empty($params['prefix']))
					$query['prefix'] = $params['prefix'];
				if (!empty($params['fmt']))
					$query['fmt'] = $params['fmt'];
				break;


			case 'tree':
				if (!empty($params['file']))
					$query['f'] = rawurlencode($params['file']);
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['hashbase']))
					$query['hb'] = GitPHP_Router::GetHash($params['hashbase'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'tag':
				if (!empty($params['hash'])) {
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
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'commit':
				if (!empty($params['hash']))
					$query['h'] = GitPHP_Router::GetHash($params['hash'], $abbreviate);
				if (!empty($params['output']))
					$query['o'] = $params['output'];
				break;


			case 'graph':
				if (!empty($params['graphtype']))
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

}
