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
	 * @param string $projectDir (deprecated)
	 * @throws Exception if parameter is not a directory
	 * @access public
	 */
	public function __construct($projectDir = '')
	{
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
		if (!GitPHP_Cache::GetObjectCacheInstance()->GetEnabled()) {

			// cache project list even if object cache is disabled (too much files)
			$simpleCache = true;
			$stat = (is_file(CACHE_PROJECTLIST)) ? stat(CACHE_PROJECTLIST) : FALSE;
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
		$this->RecurseDir($this->projectRoot);

		if (count($this->projects) > 0) {
			$projects = array();
			foreach ($this->projects as $proj) {
				$projects[] = $proj->GetProject();;
			}
			if ($simpleCache) {
				$this->CacheSaveProjectList();
			}
		}
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
		if (!$this->IsDir($dir))
			return;

		GitPHP_Log::GetInstance()->Log(sprintf('Searching directory %1$s', $dir));

		if ($dh = opendir($dir)) {
			$trimlen = strlen(GitPHP_Util::AddSlash($this->projectRoot)) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;

				if (!$this->IsDir($fullPath) || $file == '.' || $file == '..')
					continue;

				elseif ( $this->repoSupport and $file == '.repo' )
					; // check subfolders

				elseif ( substr($file,-4) != '.git') {
					// working copy repositories (git clone)
					if ( !$this->bareOnly && $this->IsDir($fullPath . '/.git') )
						$fullPath .= '/.git';
					elseif ($this->curlevel >= $this->sublevels or substr($file,0,1) == '.')
						continue;
				}

				if (is_file($fullPath . '/HEAD') || is_file($fullPath . '/ORIG_HEAD')) {
					$projectPath = substr($fullPath, $trimlen);
					if (!isset($this->projects[$projectPath])) {
						GitPHP_Log::GetInstance()->Log(sprintf('Found project %1$s', $projectPath));
						$project = $this->InstantiateProject($projectPath);
						if ($project) {

							$category = trim(substr($dir, $trimlen), '/');
							$project->SetCategory($category);

							$this->projects[$projectPath] = $project;
							unset($project);
						}
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
	 * InstantiateProject
	 *
	 * Instantiates project object
	 *
	 * @access protected
	 * @param string $proj project
	 * @return mixed project
	 */
	protected function InstantiateProject($proj)
	{
		try {

			$project = new GitPHP_Project($this->projectRoot, $proj);

			$category = trim(dirname($proj));
			if (!(empty($category) || (strpos($category, '.') === 0))) {
				$project->SetCategory($category);
			}

			if (GitPHP_Config::GetInstance()->GetValue('exportedonly', false) && !$project->GetDaemonEnabled()) {
				GitPHP_Log::GetInstance()->Log(sprintf('Project %1$s not enabled for export', $project->GetPath()));
				return null;
			}

			if ($this->projectSettings && isset($this->projectSettings[$proj])) {
				$this->ApplyProjectSettings($project, $this->projectSettings[$proj]);
			}

			return $project;

		} catch (Exception $e) {
			GitPHP_Log::GetInstance()->Log($e->getMessage());
		}

		return null;
	}

	/** Save and restore project list to prevent parsing directories
	 */
	public function CacheSaveProjectList()
	{
		$data = serialize($this->projects);
		if (!is_file(CACHE_PROJECTLIST) || $data != file_get_contents(CACHE_PROJECTLIST)) {
			return (file_put_contents(CACHE_PROJECTLIST,$data) > 0);
		}
		return true;
	}

}
