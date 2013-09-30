<?php
/**
 * Base class that all controllers extend
 *
 * @author Christopher Han <xiphux@gmail.com
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
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
	 * User list instance
	 *
	 * @var GitPHP_UserList
	 */
	protected $userList;

	/**
	 * Resource handler instance
	 *
	 * @var GitPHP_Resource
	 */
	protected $resource;

	/**
	 * Smarty instance
	 *
	 * @var Smarty
	 */
	protected $tpl;

	/**
	 * Project list
	 *
	 * @var GitPHP_ProjectListBase
	 */
	protected $projectList;

	/**
	 * Current project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Flag if this is a multi project controller
	 *
	 * @var boolean
	 */
	protected $multiProject;

	/**
	 * Parameters
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * HTTP Headers
	 *
	 * @var string[]
	 */
	protected $headers = array();

	/**
	 * Flag to preserve whitespace in output (for non-html output)
	 *
	 * @var boolean
	 */
	protected $preserveWhitespace = false;

	/**
	 * Logger instance
	 *
	 * @var GitPHP_DebugLog
	 */
	protected $log;

	/**
	 * Git executable instance
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Url router instance
	 *
	 * @var GitPHP_Router
	 */
	protected $router;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeConfig();

		$this->InitializeResource();

		$this->InitializeUserList();

		$this->EnableLogging();

		$this->InitializeGitExe();

		$this->InitializeProjectList();

		$this->InitializeSmarty();

		if ($this->multiProject) {
			$this->projectList->LoadProjects();
		}

		if (!empty($this->params['project'])) {
			$project = $this->projectList->GetProject($this->params['project']);
			if (!$project) {
				throw new GitPHP_InvalidProjectParameterException($this->params['project']);
			}
			if ($this->userList && ($this->userList->GetCount() > 0)) {
				if (!$project->UserCanAccess((!empty($_SESSION['gitphpuser']) ? $_SESSION['gitphpuser'] : null))) {
					throw new GitPHP_UnauthorizedProjectException($this->params['project']);
				}
			}
			$this->project = $project->GetProject();
		}

		if (!($this->project || $this->multiProject)) {
			throw new GitPHP_MissingProjectParameterException();
		}
	}

	/**
	 * Initialize config
	 */
	protected function InitializeConfig()
	{
		$this->config = new GitPHP_Config();
		$this->config->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');
	}

	/**
	 * Initialize resource manager
	 */
	protected function InitializeResource()
	{
		$locale = null;

		$baseurl = GitPHP_Util::BaseUrl();
		if (empty($baseurl) && $this->config->GetValue('cleanurl'))
			$baseurl = '/';

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
	 * Initialize user list
	 */
	public function InitializeUserList()
	{
		$this->userList = new GitPHP_UserList();
		$this->userList->LoadUsers(GITPHP_CONFIGDIR . 'users.conf.php');
		if ($this->userList->GetCount() > 0) {
			if (!isset($_SESSION))
				session_start();
		}
	}

	/**
	 * Initialize executable
	 *
	 * @param boolean $validate whether the exe should be validated
	 */
	protected function InitializeGitExe($validate = true)
	{
		$this->exe = new GitPHP_GitExe($this->config->GetValue('gitbin'));
		if ($this->log)
			$this->exe->AddObserver($this->log);
		if ($validate && !$this->exe->Valid()) {
			throw new GitPHP_InvalidGitExecutableException($this->exe->GetBinary());
		}
	}

	/**
	 * Initialize project list
	 */
	protected function InitializeProjectList()
	{
		if (file_exists(GITPHP_CONFIGDIR . 'projects.conf.php')) {
			$this->projectList = GitPHP_ProjectList::Instantiate($this->config, GITPHP_CONFIGDIR . 'projects.conf.php', false);
		} else {
			$this->projectList = GitPHP_ProjectList::Instantiate($this->config, GITPHP_CONFIGDIR . 'gitphp.conf.php', true);
		}

		$this->projectList->SetMemoryCache(new GitPHP_MemoryCache($this->config->GetValue('objectmemory')));
		if ($this->config->GetValue('objectcache')) {
			$strategy = null;
			$servers = $this->config->GetValue('memcache');
			if ($servers) {
				if (class_exists('Memcached')) {
					$strategy = new GitPHP_Cache_Memcached($servers);
				} else if (class_exists('Memcache')) {
					$strategy = new GitPHP_Cache_Memcache($servers);
				} else {
					throw new GitPHP_MissingMemcacheException();
				}
			} else {
				$strategy = new GitPHP_Cache_File(GITPHP_CACHEDIR . 'objects', $this->config->GetValue('objectcachecompress'));
			}
			$cache = new GitPHP_Cache($strategy);
			$cache->SetLifetime($this->config->GetValue('objectcachelifetime'));
			$this->projectList->SetCache($cache);
		}

		$this->projectList->SetExe($this->exe);

		if ($this->log)
			$this->projectList->AddObserver($this->log);

	}

	/**
	 * Initialize smarty
	 */
	protected function InitializeSmarty()
	{
		require_once(GITPHP_SMARTYDIR . 'Smarty.class.php');
		$this->tpl = new Smarty;
		$this->tpl->error_reporting = E_ALL & ~E_NOTICE;
		$this->tpl->merge_compiled_includes = true;
		$this->tpl->addPluginsDir(GITPHP_INCLUDEDIR . 'smartyplugins');

		if ($this->config->GetValue('cache')) {
			$cacheDir = GITPHP_CACHEDIR . 'templates';

			if (file_exists($cacheDir)) {
				if (!is_dir($cacheDir)) {
					throw new Exception($cacheDir . ' exists but is not a directory');
				} else if (!is_writable($cacheDir)) {
					throw new Exception($cacheDir . ' is not writable');
				}
			} else {
				if (!mkdir($cacheDir, 0777))
					throw new Exception($cacheDir . ' could not be created');
				chmod($cacheDir, 0777);
			}
			$this->tpl->setCacheDir($cacheDir);

			$this->tpl->caching = Smarty::CACHING_LIFETIME_SAVED;
			if ($this->config->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = $this->config->GetValue('cachelifetime');
			}

			$servers = $this->config->GetValue('memcache');
			if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
				$this->tpl->registerCacheResource('memcache', new GitPHP_CacheResource_Memcache($servers));
				$this->tpl->caching_type = 'memcache';
			}

		}

	}

	/**
	 * Set router instance
	 *
	 * @param GitPHP_Router $router router
	 */
	public function SetRouter($router)
	{
		$this->router = $router;
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
	 * Get log instance
	 *
	 * @return GitPHP_DebugLog
	 */
	public function GetLog()
	{
		return $this->log;
	}

	/**
	 * Enable logging
	 */
	public function EnableLogging()
	{
		if ($this->log)
			return;

		$debug = $this->config->GetValue('debug');
		if ($debug) {
			$this->log = GitPHP_DebugLog::GetInstance();
			$this->log->init($debug, $this->config->GetValue('benchmark'));
			$this->log->SetStartTime(GITPHP_START_TIME);
			$this->log->SetStartMemory(GITPHP_START_MEM);
			if ($this->exe)
				$this->exe->AddObserver($this->log);
			if ($this->projectList)
				$this->projectList->AddObserver($this->log);
		}
	}

	/**
	 * Disable logging
	 */
	protected function DisableLogging()
	{
		if (!$this->log)
			return;

		if ($this->projectList)
			$this->projectList->RemoveObserver($this->log);
		if ($this->exe)
			$this->exe->RemoveObserver($this->log);

		$this->log->SetEnabled(false);

		$this->log = null;
	}

	/**
	 * Gets the project for this controller
	 *
	 * @return GitPHP_Project|null project
	 */
	public function GetProject()
	{
		if ($this->project)
			return $this->projectList->GetProject($this->project);
		return null;
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected abstract function GetTemplate();

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected abstract function GetCacheKey();

	/**
	 * Get the prefix for all cache keys
	 *
	 * @param boolean $projectKeys include project-specific key pieces
	 * @return string cache key prefix
	 */
	private function GetCacheKeyPrefix($projectKeys = true)
	{
		if ($this->resource)
			$cacheKeyPrefix = $this->resource->GetLocale();
		else
			$cacheKeyPrefix = 'en_US';

		if ($this->projectList) {
			$cacheKeyPrefix .= '|' . sha1(serialize($this->projectList->GetProjectListConfig())) . '|' . sha1(serialize($this->projectList->GetProjectSettings()));
		}
		if ($this->project && $projectKeys) {
			$cacheKeyPrefix .= '|' . sha1($this->project);
		}

		return $cacheKeyPrefix;
	}

	/**
	 * Get the full cache key
	 *
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
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public abstract function GetName($local = false);

	/**
	 * Set a parameter
	 *
	 * @param string $key key to set
	 * @param mixed $value value to set
	 */
	public function SetParam($key, $value)
	{
		if (empty($key))
			return;

		if (empty($value))
			unset($this->params[$key]);

		if (is_string($value))
			$value = str_replace(chr(0), '', $value);

		$this->params[$key] = $value;
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		$this->headers[] = 'Content-Type: text/html; charset=UTF-8';
	}

	/**
	 * Loads data for this template
	 */
	protected abstract function LoadData();

	/**
	 * Loads common data used by all templates
	 */
	private function LoadCommonData()
	{
		global $gitphp_version, $gitphp_appstring;

		$this->tpl->assign('version', $gitphp_version);

		$stylesheet = $this->config->GetValue('stylesheet');
		if ($stylesheet == 'gitphp.css') {
			// backwards compatibility
			$stylesheet = 'gitphpskin.css';
		}
		$this->tpl->assign('stylesheet', preg_replace('/\.css$/', '', $stylesheet));

		$this->tpl->assign('javascript', $this->config->GetValue('javascript'));
		$this->tpl->assign('googlejs', $this->config->GetValue('googlejs'));
		if ($this->config->HasKey('title')) {
			$this->tpl->assign('pagetitle', $this->config->GetValue('title'));
		} else {
			$this->tpl->assign('pagetitle', $gitphp_appstring);
		}
		if ($this->config->HasKey('homelink')) {
			$this->tpl->assign('homelink', $this->config->GetValue('homelink'));
		} else {
			if ($this->resource)
				$this->tpl->assign('homelink', $this->resource->translate('projects'));
			else
				$this->tpl->assign('homelink', 'projects');
		}
		$this->tpl->assign('action', $this->GetName());
		$this->tpl->assign('actionlocal', $this->GetName(true));
		if ($this->project)
			$this->tpl->assign('project', $this->GetProject());
		if ($this->config->GetValue('search'))
			$this->tpl->assign('enablesearch', true);
		if ($this->config->GetValue('filesearch'))
			$this->tpl->assign('filesearch', true);
		if (isset($this->params['search']))
			$this->tpl->assign('search', $this->params['search']);
		if (isset($this->params['searchtype']))
			$this->tpl->assign('searchtype', $this->params['searchtype']);
		if ($this->resource) {
			$this->tpl->assign('currentlocale', $this->resource->GetLocale());
			$this->tpl->assign('currentprimarylocale', $this->resource->GetPrimaryLocale());
			$this->tpl->assign('resource', $this->resource);
		} else {
			$this->tpl->assign('currentlocale', 'en_US');
			$this->tpl->assign('currentprimarylocale', 'en');
		}
		$this->tpl->assign('supportedlocales', GitPHP_Resource::SupportedLocales(true));
		if ($this->config->GetValue('graphs'))
			$this->tpl->assign('enablegraphs', true);

		$this->tpl->assign('baseurl', GitPHP_Util::BaseUrl());

		$requesturl = $_SERVER['REQUEST_URI'];
		$querypos = strpos($requesturl, '?');
		if ($querypos !== false)
			$requesturl = substr($requesturl, 0, $querypos);
		$this->tpl->assign('requesturl', $requesturl);

		if ($this->router) {
			$this->router->SetCleanUrl($this->config->GetValue('cleanurl') ? true : false);
			$this->router->SetAbbreviate($this->config->GetValue('abbreviateurl') ? true : false);
			if ($this->config->GetValue('self')) {
				$this->router->SetBaseUrl($this->config->GetValue('self'));
			}
			$this->tpl->assign('router', $this->router);
		}

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
				if (!(empty($var) || empty($val) || ($var == 'q'))) {
					$getvarsmapped[$var] = urldecode($val);
				}
			}
		}
		$this->tpl->assign('requestvars', $getvarsmapped);

		$this->tpl->assign('snapshotformats', GitPHP_Archive::SupportedFormats());

		if ($this->userList && ($this->userList->GetCount() > 0)) {
			$this->tpl->assign('loginenabled', true);
			if (!empty($_SESSION['gitphpuser'])) {
				$user = $this->userList->GetUser($_SESSION['gitphpuser']);
				if ($user) {
					$this->tpl->assign('loggedinuser', $user->GetUsername());
				}
			}
		}

		if ($this->log && $this->log->GetEnabled()) {
			$this->tpl->assign('debug', true);
		}
	}

	/**
	 * Renders any special headers
	 */
	public function RenderHeaders()
	{
		$this->LoadHeaders();

		if (count($this->headers) > 0) {
			$hascontenttype = false;
			foreach ($this->headers as $hdr) {
				if (empty($hdr))
					continue;

				if (strncmp($hdr, 'Content-Type:', 13) === 0) {
					if ($hascontenttype)
						throw new Exception('Duplicate Content-Type header');
					$hascontenttype = true;
				}
				header($hdr);
			}
		}
	}

	/**
	 * Renders the output
	 */
	public function Render()
	{
		if (($this->config->GetValue('cache') == true) && ($this->config->GetValue('cacheexpire') === true))
			$this->CacheExpire();

		$log = GitPHP_DebugLog::GetInstance();

		if (!$this->tpl->isCached($this->GetTemplate(), $this->GetFullCacheKey())) {
			$this->tpl->clearAllAssign();

			$log->TimerStart();
			$this->LoadCommonData();
			$log->TimerStop('Common data');

			$log->TimerStart();
			$this->LoadData();
			$log->TimerStop('Data');
		}

		if (!$this->preserveWhitespace) {
			//$this->tpl->loadFilter('output', 'trimwhitespace');
		}

		$log->TimerStart();
		$this->tpl->display($this->GetTemplate(), $this->GetFullCacheKey());
		$log->TimerStop('Render');

		$this->tpl->clearAllAssign();

		if ($this->projectList)
			$log->Log('MemoryCache', 'Count: ' . $this->projectList->GetMemoryCache()->GetCount());

		if ($log->GetEnabled()) {
			$this->tpl->assign('debuglog', $log);
			$this->tpl->display('debug.tpl');
		}
	}

	/**
	 * Expires the cache
	 *
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
