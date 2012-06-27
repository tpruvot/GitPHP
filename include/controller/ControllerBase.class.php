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
	 * Constructor
	 */
	public function __construct()
	{
		$this->config = GitPHP_Config::GetInstance();

		$log = GitPHP_DebugLog::GetInstance();
		if ($log && $log->GetEnabled())
			$this->log = $log;

		$this->InitializeGitExe();

		$this->InitializeProjectList();

		$this->InitializeSmarty();

		if ($this->multiProject) {
			$this->projectList->LoadProjects();
		}

		if (isset($_GET['p'])) {
			$project = $this->projectList->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$project) {
				throw new GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
			}
			$this->project = $project->GetProject();
		}

		if (!($this->project || $this->multiProject)) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}

		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];
		if (isset($_GET['st']))
			$this->params['searchtype'] = $_GET['st'];

		$this->ReadQuery();
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
			throw new GitPHP_MessageException(sprintf(__('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'), $this->exe->GetBinary(), 'gitbin'), true, 500);
		}
	}

	/**
	 * Initialize project list
	 */
	protected function InitializeProjectList()
	{
		if (file_exists(GITPHP_CONFIGDIR . 'projects.conf.php')) {
			$this->projectList = GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'projects.conf.php', false);
		} else {
			$this->projectList = GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'gitphp.conf.php', true);
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

		if ($this->config->GetValue('cache', false)) {
			$this->tpl->caching = Smarty::CACHING_LIFETIME_SAVED;
			if ($this->config->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = $this->config->GetValue('cachelifetime');
			}

			$servers = $this->config->GetValue('memcache', null);
			if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
				$this->tpl->registerCacheResource('memcache', new GitPHP_CacheResource_Memcache($servers));
				$this->tpl->caching_type = 'memcache';
			}

		}

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
	 * Disable logging
	 */
	protected function DisableLogging()
	{
		if (!$this->log)
			return;

		$this->projectList->RemoveObserver($this->log);

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
		$cacheKeyPrefix = GitPHP_Resource::GetLocale();

		if ($this->projectList) {
			$cacheKeyPrefix .= '|' . sha1(serialize($this->projectList->GetConfig())) . '|' . sha1(serialize($this->projectList->GetSettings()));
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
	 * Read query into parameters
	 */
	protected abstract function ReadQuery();

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

		$this->params[$key] = $value;
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
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
		$this->tpl->assign('currentlocale', GitPHP_Resource::GetLocale());
		$this->tpl->assign('supportedlocales', GitPHP_Resource::SupportedLocales($this->config->GetValue('debug', false)));

		$scripturl = $_SERVER['SCRIPT_NAME'];
		$fullscripturl = '';
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
			$fullscripturl = 'https://';
		else
			$fullscripturl = 'http://';
		$fullscripturl .= $_SERVER['HTTP_HOST'] . $scripturl;

		if (GitPHP_Config::GetInstance()->HasKey('self')) {
			$selfurl = GitPHP_Config::GetInstance()->GetValue('self');
			if (!empty($selfurl)) {
				if (substr($selfurl, -4) != '.php') {
					$selfurl = GitPHP_Util::AddSlash($selfurl);
				}
				$fullscripturl = $selfurl;
			}
		}
		$this->tpl->assign('scripturl', $scripturl);
		$this->tpl->assign('fullscripturl', $fullscripturl);

		$getvars = explode('&', $_SERVER['QUERY_STRING']);
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
	 * Renders any special headers
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
	 * Renders the output
	 */
	public function Render()
	{
		if (($this->config->GetValue('cache', false) == true) && ($this->config->GetValue('cacheexpire', true) === true))
			$this->CacheExpire();

		if (!$this->tpl->isCached($this->GetTemplate(), $this->GetFullCacheKey())) {
			$this->tpl->clearAllAssign();
			if ($this->log && $this->log->GetBenchmark())
				$this->log->Log("Data load begin");
			$this->LoadCommonData();
			$this->LoadData();
			if ($this->log && $this->log->GetBenchmark())
				$this->log->Log("Data load end");
		}

		if (!$this->preserveWhitespace) {
			//$this->tpl->loadFilter('output', 'trimwhitespace');
		}

		if ($this->log && $this->log->GetBenchmark())
			$this->log->Log("Smarty render begin");
		$this->tpl->display($this->GetTemplate(), $this->GetFullCacheKey());
		if ($this->log && $this->log->GetBenchmark())
			$this->log->Log("Smarty render end");

		$this->tpl->clearAllAssign();

		if ($this->log)
			$this->log->Log('MemoryCache count: ' . $this->projectList->GetMemoryCache()->GetCount());
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
