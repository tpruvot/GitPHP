<?php
/**
 * GitPHP ProjectListDirectory
 *
 * Lists all projects in a given directory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

define('CACHE_PROJECTLIST',GITPHP_CACHE.'projectlist.tmp');

/**
 * ProjectListDirectory class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListDirectory extends GitPHP_ProjectListBase
{

	/**
	 * projectDir
	 *
	 * Stores projectlist directory internally
	 *
	 * @access protected
	 */
	protected $projectDir = '';

	/**
	 * bareOnly
	 *
	 * Ignore working git repositories (project/.git)
	 */
	protected $bareOnly = true;

	/**
	 * repoSupport
	 *
	 * Go inside android source .repo folders
	 */
	protected $repoSupport = false;

	/**
	 * sublevels
	 *
	 * Search in subfolders, maximum recursive level
	 */
	protected $sublevels = 0;

	/**
	 * curlevel
	 *
	 * Search in subfolders, current recursive level
	 */
	protected $curlevel = 0;


	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectDir directory to search
	 * @throws Exception if parameter is not a directory
	 * @access public
	 */
	public function __construct($projectDir)
	{
		if (!is_dir($projectDir)) {
			throw new Exception(sprintf(__('%1$s is not a directory'), $projectDir));
		}

		$this->projectDir = GitPHP_Util::AddSlash($projectDir);

		$Config = GitPHP_Config::GetInstance();

		$this->bareOnly    = $Config->GetValue('bareonly', true);
		$this->sublevels   = $Config->GetValue('subfolder_levels', 0);
		$this->repoSupport = $Config->GetValue('reposupport', false);

		parent::__construct();
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 */
	protected function PopulateProjects()
	{
		// HACK workaround for strange behavior of CACHING_LIFETIME_SAVED in smarty 3
		$oldLifetime = GitPHP_Cache::GetObjectCacheInstance()->GetLifetime();
		GitPHP_Cache::GetObjectCacheInstance()->SetLifetime(GitPHP_Config::GetInstance()->GetValue('cachelifetime', 3600));

		$key = 'projectdir|' . $this->projectDir . '|projectlist|directory';
		$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($key);
		if ($cached && (count($cached) > 0)) {
			foreach ($cached as $proj) {
				$this->AddProject($proj);
			}
			GitPHP_Log::GetInstance()->Log('Loaded ' . count($this->projects) . ' projects from cache');
			GitPHP_Cache::GetObjectCacheInstance()->SetLifetime($oldLifetime);
			return;

		} elseif (!GitPHP_Cache::GetObjectCacheInstance()->GetEnabled()) {

			// cache project list even if object cache is disabled (too much files)
			$simpleCache = true;
			$stat = stat(CACHE_PROJECTLIST);
			if ($stat !== FALSE) {
				$cache_life = '180';  //caching time, in seconds
				$filemtime = max($stat['mtime'], $stat['ctime']);
				if  (time() - $filemtime >= $cache_life) {
					GitPHP_Log::GetInstance()->Log('ProjectListDirCache: expired, reloading...');
				} else {
					$data = file_get_contents(CACHE_PROJECTLIST);
					$projects = unserialize($data);
					if (count($projects) > 0) {
						GitPHP_Log::GetInstance()->Log('loaded '.count($projects).' projects from cache');
						$this->projects = $projects;
						return;
					}
				}
			}
		}

		$this->curlevel = 0;
		$this->RecurseDir($this->projectDir);

		if (count($this->projects) > 0) {
			$projects = array();
			foreach ($this->projects as $proj) {
				$projects[] = $proj->GetProject();;
			}
			GitPHP_Cache::GetObjectCacheInstance()->Set($key, $projects, GitPHP_Config::GetInstance()->GetValue('cachelifetime', 3600));
			if ($simpleCache) {
				$this->CacheSaveProjectList();
			}
		}
		GitPHP_Cache::GetObjectCacheInstance()->SetLifetime($oldLifetime);
	}

	/**
	 * RecurseDir
	 *
	 * Recursively searches for projects
	 *
	 * @param string $dir directory to recurse into
	 */
	private function RecurseDir($dir)
	{
		if (!(is_dir($dir) && is_readable($dir)))
			return;

		GitPHP_Log::GetInstance()->Log(sprintf('Searching directory %1$s', $dir));

		if ($dh = opendir($dir)) {
			$trimlen = strlen($this->projectDir) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;

				if (!is_dir($fullPath) or $file == '.' or $file == '..')
					continue;

				elseif ( $this->repoSupport and $file == '.repo' )
					; // check subfolders

				elseif ( substr($file,-4) != '.git') {
					// working copy repositories (git clone)
					if ( !$this->bareOnly && is_dir($fullPath . '/.git') )
						$fullPath .= '/.git';
					elseif ($this->curlevel >= $this->sublevels or substr($file,0,1) == '.')
						continue;
				}

				if (is_file($fullPath . '/HEAD')) {
					$projectPath = substr($fullPath, $trimlen);
					GitPHP_Log::GetInstance()->Log(sprintf('Found project %1$s', $projectPath));
					try {
						$proj = new GitPHP_Project($this->projectDir, $projectPath);
						$proj->SetCategory(trim(substr($dir, strlen($this->projectDir)), '/'));
						if ((!GitPHP_Config::GetInstance()->GetValue('exportedonly', false)) || $proj->GetDaemonEnabled()) {
							$this->projects[$projectPath] = $proj;
						}
					} catch (Exception $e) {
					}
				} elseif ($this->curlevel < $this->sublevels) {
					$this->curlevel++;
					$this->RecurseDir($fullPath);
					$this->curlevel--;
				} else {
					GitPHP_Log::GetInstance()->Log(sprintf('Skipping %1$s', $fullPath));
				}

			}
			closedir($dh);
		}
	}

	/**
	 * AddProject
	 *
	 * Add project to collection
	 *
	 * @access private
	 */
	private function AddProject($projectPath)
	{
		try {
			$proj = new GitPHP_Project($this->projectDir, $projectPath);
			$category = trim(dirname($projectPath));
			if (!(empty($category) || (strpos($category, '.') === 0))) {
				$proj->SetCategory($category);
			}
			if ((!GitPHP_Config::GetInstance()->GetValue('exportedonly', false)) || $proj->GetDaemonEnabled()) {
				$this->projects[$projectPath] = $proj;
			}
		} catch (Exception $e) {
			GitPHP_Log::GetInstance()->Log($e->getMessage());
		}
	}
	
	/** Save and restore project list to prevent parsing directories
	 */
	public function CacheSaveProjectList()
	{
		$data = serialize($this->projects);
		return (file_put_contents(CACHE_PROJECTLIST,$data) > 0);
	}

}
