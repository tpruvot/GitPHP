<?php
/**
 * GitPHP ControllerBase
 *
 * Base class that all controllers extend
 *
 * @author Christopher Han <xiphux@gmail.com
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * ControllerBase class
 *
 * @package GitPHP
 * @subpackage Controller
 * @abstract
 */
abstract class GitPHP_ControllerBase
{
	/**
	 * Config handler instance
	 *
	 * @var GitPHP_Config
	 */
	protected $config;

	/**
	 * @var GitPHP_Resource
	 */
	protected $resource;

	/**
	 * tpl
	 *
	 * Smarty instance
	 *
	 * @access protected
	 */
	protected $tpl;

	/**
	 * project
	 *
	 * Current project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * multiProject
	 *
	 * Flag if this is a multi project controller
	 *
	 * @access protected
	 */
	protected $multiProject;

	/**
	 * params
	 *
	 * Parameters
	 *
	 * @access protected
	 */
	protected $params = array();

	/**
	 * headers
	 *
	 * Headers
	 *
	 * @access protected
	 */
	protected $headers = array();

	/**
	 * preserveWhitespace
	 *
	 * Flag to preserve whitespace in output
	 * (for non-html output)
	 *
	 * @access protected
	 */
	protected $preserveWhitespace = false;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeConfig();

		$this->InitializeResource();
	}

	/**
	 * Initialize config
	 */
	protected function InitializeConfig()
	{
		$this->config = GitPHP_Config::GetInstance();
		$this->config->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');
	}

	/**
	 * Initialize resource manager
	 */
	protected function InitializeResource()
	{
		$locale = null;

		$baseurl = GitPHP_Util::BaseUrl();

		if (!empty($this->params['lang'])) {
			/*
			 * User picked something
			 */
			setcookie(GitPHP_Resource::LocaleCookie, $this->params['lang'], time()+GitPHP_Resource::LocaleCookieLifetime, $baseurl);
			$locale = $this->params['lang'];
		} else if (!empty($_COOKIE[GitPHP_Resource::LocaleCookie])) {
			/**
			 * Returning user with a preference
			 */
			$locale = $_COOKIE[GITPHP_Resource::LocaleCookie];
		} else {
			/*
			 * User's first time here, try by HTTP_ACCEPT_LANGUAGE
			 */
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$httpAcceptLang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				$locale = GitPHP_Resource::FindPreferredLocale($_SERVER['HTTP_ACCEPT_LANGUAGE']);
				if (!empty($locale)) {
					setcookie(GitPHP_Resource::LocaleCookie, $locale, time()+GitPHP_Resource::LocaleCookieLifetime, $baseurl);
				}
			}
		}

		if (empty($locale) && $this->config) {
			/*
			 * No preference, fall back on setting
			 */
			$locale = $this->config->GetValue('locale');
		}

		if (!empty($locale) && ($locale != 'en_US')) {
			try {
				$this->resource = new GitPHP_Resource($locale);
			} catch (Exception $e) {
			}
		}
	}

	/**
	 * @access public
	 * @return mixed controller object
	 * @throws Exception on invalid project
	 */
	public function __construct()
	{
		$this->Initialize();

		require_once(GITPHP_SMARTYDIR . 'Smarty.class.php');
		$this->tpl = new Smarty;
		$this->tpl->error_reporting = E_ALL & ~E_NOTICE;
		$this->tpl->merge_compiled_includes = true;
		$this->tpl->addPluginsDir(GITPHP_INCLUDEDIR . 'smartyplugins');

		if ($this->config->GetValue('cache', false)) {
			$this->tpl->caching = Smarty::CACHING_LIFETIME_SAVED;
			if ($this->config->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = $this->config->GetValue('cachelifetime');
			}

			$servers = $this->config->GetValue('memcache', null);
			if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
				$this->tpl->caching_type = 'memcache';
			}

		}

		if (isset($_GET['p'])) {
			$project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$project) {
				throw new GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
			}
			$this->project = $project->GetProject();
		}

		if (!($this->project || $this->multiProject)) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}

		if ($this->multiProject) {
			GitPHP_ProjectList::GetInstance()->LoadProjects();
		}

		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];
		if (isset($_GET['st']))
			$this->params['searchtype'] = $_GET['st'];

		$this->ReadQuery();
	}

	/**
	 * Get config instance
	 *
	 * @return GitPHP_Config
	 */
	public function GetConfig()
	{
		return $this->config;
	}

	/**
	 * GetProject
	 *
	 * Gets the project for this controller
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		if ($this->project)
			return GitPHP_ProjectList::GetInstance()->GetProject($this->project);
		return null;
	}

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
	 * @abstract
	 * @return string template filename
	 */
	protected abstract function GetTemplate();

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @abstract
	 * @return string cache key
	 */
	protected abstract function GetCacheKey();

	/**
	 * GetCacheKeyPrefix
	 *
	 * Get the prefix for all cache keys
	 *
	 * @access private
	 * @param string $projectKeys include project-specific key pieces
	 * @return string cache key prefix
	 */
	private function GetCacheKeyPrefix($projectKeys = true)
	{
		$cacheKeyPrefix = GitPHP_Resource::GetLocale();

		$projList = GitPHP_ProjectList::GetInstance();
		if ($projList) {
			$cacheKeyPrefix .= '|' . sha1(serialize($projList->GetConfig())) . '|' . sha1(serialize($projList->GetSettings()));
			unset($projList);
		}
		if ($this->project && $projectKeys) {
			$cacheKeyPrefix .= '|' . sha1($this->project);
		}
		
		return $cacheKeyPrefix;
	}

	/** 
	 * GetFullCacheKey
	 *
	 * Get the full cache key
	 *
	 * @access protected
	 * @return string full cache key
	 */
	protected function GetFullCacheKey()
	{
		$cacheKey = $this->GetCacheKeyPrefix();

		$subCacheKey = $this->GetCacheKey();

		if (!empty($subCacheKey))
			$cacheKey .= '|' . $subCacheKey;

		if (strlen($cacheKey) > 100) {
			$cacheKey = sha1($cacheKey);
		}

		return $cacheKey;
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @abstract
	 * @access public
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public abstract function GetName($local = false);

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @abstract
	 * @access protected
	 */
	protected abstract function ReadQuery();

	/**
	 * SetParam
	 *
	 * Set a parameter
	 *
	 * @access protected
	 * @param string $key key to set
	 * @param mixed $value value to set
	 */
	public function SetParam($key, $value)
	{
		if (empty($key))
			return;

		if (empty($value))
			unset($this->params[$key]);

		$this->params[$key] = $value;
	}

	/**
	 * LoadHeaders
	 *
	 * Loads headers for this template
	 *
	 * @access protected
	 */
	protected function LoadHeaders()
	{
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 * @abstract
	 */
	protected abstract function LoadData();

	/**
	 * LoadCommonData
	 *
	 * Loads common data used by all templates
	 *
	 * @access private
	 */
	private function LoadCommonData()
	{
		global $gitphp_version, $gitphp_appstring;

		$this->tpl->assign('version', $gitphp_version);

		$stylesheet = $this->config->GetValue('stylesheet', 'gitphpskin.css');
		if ($stylesheet == 'gitphp.css') {
			// backwards compatibility
			$stylesheet = 'gitphpskin.css';
		}
		$this->tpl->assign('stylesheet', preg_replace('/\.css$/', '', $stylesheet));

		$this->tpl->assign('javascript', $this->config->GetValue('javascript', true));
		$this->tpl->assign('googlejs', $this->config->GetValue('googlejs', false));
		$this->tpl->assign('pagetitle', $this->config->GetValue('title', $gitphp_appstring));
		$this->tpl->assign('homelink', $this->config->GetValue('homelink', __('projects')));
		$this->tpl->assign('action', $this->GetName());
		$this->tpl->assign('actionlocal', $this->GetName(true));
		if ($this->project)
			$this->tpl->assign('project', $this->GetProject());
		if ($this->config->GetValue('search', true))
			$this->tpl->assign('enablesearch', true);
		if ($this->config->GetValue('filesearch', true))
			$this->tpl->assign('filesearch', true);
		if (isset($this->params['search']))
			$this->tpl->assign('search', $this->params['search']);
		if (isset($this->params['searchtype']))
			$this->tpl->assign('searchtype', $this->params['searchtype']);

		if ($this->resource) {
			$this->tpl->assign('currentlocale', $this->resource->GetLocale());
			$this->tpl->assign('resource', $this->resource);
		} else {
			$this->tpl->assign('currentlocale', 'en_US');
		}
		$this->tpl->assign('supportedlocales', GitPHP_Resource::SupportedLocales(true));

		$scripturl = $_SERVER['SCRIPT_NAME'];

		if ($this->config->HasKey('self')) {
			$selfurl = $this->config->GetValue('self');
			if (!empty($selfurl)) {
				if (substr($selfurl, -4) != '.php') {
					$selfurl = GitPHP_Util::AddSlash($selfurl);
				}
			}
		}
		$this->tpl->assign('scripturl', $scripturl);

		$this->tpl->assign('baseurl', GitPHP_Util::BaseUrl());

		$requesturl = $_SERVER['REQUEST_URI'];
		$querypos = strpos($requesturl, '?');
		if ($querypos !== false)
			$requesturl = substr($requesturl, 0, $querypos);
		$this->tpl->assign('requesturl', $requesturl);

		$getvars = array();
		if (isset($_SERVER['QUERY_STRING'])) {
			$getvars = explode('&', $_SERVER['QUERY_STRING']);
		}

		$getvarsmapped = array();
		foreach ($getvars as $varstr) {
			$eqpos = strpos($varstr, '=');
			if ($eqpos > 0) {
				$var = substr($varstr, 0, $eqpos);
				$val = substr($varstr, $eqpos + 1);
				if (!(empty($var) || empty($val))) {
					$getvarsmapped[$var] = urldecode($val);
				}
			}
		}
		$this->tpl->assign('requestvars', $getvarsmapped);

		$this->tpl->assign('snapshotformats', GitPHP_Archive::SupportedFormats());
	}

	/**
	 * RenderHeaders
	 *
	 * Renders any special headers
	 *
	 * @access public
	 */
	public function RenderHeaders()
	{
		$this->LoadHeaders();

		if (count($this->headers) > 0) {
			foreach ($this->headers as $hdr) {
				header($hdr);
			}
		}
	}

	/**
	 * Render
	 *
	 * Renders the output
	 *
	 * @access public
	 */
	public function Render()
	{
		if (($this->config->GetValue('cache', false) == true) && ($this->config->GetValue('cacheexpire', true) === true))
			$this->CacheExpire();

		if (!$this->tpl->isCached($this->GetTemplate(), $this->GetFullCacheKey())) {
			$this->tpl->clearAllAssign();
			if (GitPHP_Log::GetInstance()->GetBenchmark())
				GitPHP_Log::GetInstance()->Log("Data load begin");
			$this->LoadCommonData();
			$this->LoadData();
			if (GitPHP_Log::GetInstance()->GetBenchmark())
				GitPHP_Log::GetInstance()->Log("Data load end");
		}

		if (!$this->preserveWhitespace) {
			//$this->tpl->loadFilter('output', 'trimwhitespace');
		}

		if (GitPHP_Log::GetInstance()->GetBenchmark())
			GitPHP_Log::GetInstance()->Log("Smarty render begin");
		$this->tpl->display($this->GetTemplate(), $this->GetFullCacheKey());
		if (GitPHP_Log::GetInstance()->GetBenchmark())
			GitPHP_Log::GetInstance()->Log("Smarty render end");

		$this->tpl->clearAllAssign();
	}

	/**
	 * CacheExpire
	 *
	 * Expires the cache
	 *
	 * @access public
	 * @param boolean $expireAll expire the whole cache
	 */
	public function CacheExpire($expireAll = false)
	{
		if ($expireAll) {
			$this->tpl->clearAllCache();
			return;
		}

		if (!$this->project)
			return;

		$epoch = $this->GetProject()->GetEpoch();
		if (empty($epoch))
			return;

		$age = $this->GetProject()->GetAge();

		$this->tpl->clearCache(null, $this->GetCacheKeyPrefix(), null, $age);
		$this->tpl->clearCache('projectlist.tpl', $this->GetCacheKeyPrefix(false), null, $age);
	}

}
