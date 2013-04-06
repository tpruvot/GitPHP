<?php

defined('GITPHP_CACHE_PROJECTLIST') || define('GITPHP_CACHE_PROJECTLIST', GITPHP_CACHEDIR.'projectlist.tmp');

/**
 * Lists all projects in a given directory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectListDirectory extends GitPHP_ProjectListBase
{
	/**
	 * Constants
	 */
	const CACHE_PROJECTLIST = GITPHP_CACHE_PROJECTLIST;

	/**
	 * Whether to only list exported projects
	 * @var boolean
	 */
	protected $exportedOnly = false;

	/**
	 * Ignore working git repositories (project/.git)
	 */
	protected $bareOnly = true;

	/**
	 * Go inside android source .repo folders
	 */
	protected $repoSupport = false;

	/**
	 * Search in subfolders, maximum recursive level
	 */
	protected $sublevels = 0;

	/**
	 * Search in subfolders, current recursive level
	 */
	protected $curlevel = 0;


	/**
	 * Constructor
	 *
	 * @param string $projectRoot project root
	 * @param boolean $exportedOnly whether to only allow exported projects
	 * @throws Exception if parameter is not a directory
	 */
	public function __construct($projectRoot, $exportedOnly = false)
	{
		$this->exportedOnly = $exportedOnly;

		$Config = GitPHP_Config::GetInstance();

		$this->bareOnly    = $Config->GetValue('bareonly', true);
		$this->sublevels   = $Config->GetValue('subfolder_levels', 0);
		$this->repoSupport = $Config->GetValue('reposupport', false);

		parent::__construct($projectRoot);

		$this->SetConfig($Config);
	}

	/**
	 * Gets whether this list only allows exported projects
	 *
	 * @return boolean
	 */
	public function GetExportedOnly()
	{
		return $this->exportedOnly;
	}

	/**
	 * Populates the internal list of projects
	 */
	protected function PopulateProjects()
	{
		$simpleCache = false;

		if (!$this->config->GetValue('cache')) {

			// cache project list even if object cache is disabled (too much files)
			$simpleCache = true;
			$stat = (is_file(self::CACHE_PROJECTLIST)) ? stat(self::CACHE_PROJECTLIST) : FALSE;
			if ($stat !== FALSE) {
				$cache_life = '180';  //caching time, in seconds
				$filemtime = max($stat['mtime'], $stat['ctime']);
				if  (time() - $filemtime >= $cache_life) {
					$this->Log('ProjectListDirCache: expired, reloading...');
				} else {
					$data = file_get_contents(self::CACHE_PROJECTLIST);
					$projects = unserialize($data);
					if (count($projects) > 0) {
						$this->Log('loaded '.count($projects).' projects from cache');
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
				// todo: check new xiphux Cache_File class
				$this->CacheSaveProjectList();
			}
		}
	}

	/**
	 * Recursively searches for projects
	 *
	 * @param string $dir directory to recurse into
	 */
	private function RecurseDir($dir)
	{
		if (!GitPHP_Util::IsDir($dir))
			return;

		$this->Log(sprintf('Searching directory %1$s', $dir));

		if ($dh = opendir($dir)) {
			$trimlen = strlen(GitPHP_Util::AddSlash($this->projectRoot)) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;

				if (!GitPHP_Util::IsDir($fullPath) || $file == '.' || $file == '..')
					continue;

				elseif ( $this->repoSupport and $file == '.repo' )
					; // check subfolders

				elseif ( substr($file,-4) != '.git') {
					// working copy repositories (git clone)
					if ( !$this->bareOnly && GitPHP_Util::IsDir($fullPath . '/.git') )
						$fullPath .= '/.git';
					elseif ($this->curlevel >= $this->sublevels or substr($file,0,1) == '.')
						continue;
				}

				if (is_file($fullPath . '/HEAD') || is_file($fullPath . '/ORIG_HEAD')) {
					$projectPath = substr($fullPath, $trimlen);
					if (!isset($this->projects[$projectPath])) {
						$this->Log(sprintf('Found project %1$s', $projectPath));
						$project = $this->LoadProject($projectPath);
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
					$this->Log(sprintf('Skipping %1$s', $fullPath));
				}
			}
			closedir($dh);
		}
	}

	/**
	 * Loads a project
	 *
	 * @param string $proj project
	 * @return GitPHP_Project project
	 */
	protected function LoadProject($proj)
	{
		try {

			$project = new GitPHP_Project($this->projectRoot, $proj);

			$category = trim(dirname($proj));
			if (!(empty($category) || (strpos($category, '.') === 0))) {
				$project->SetCategory($category);
			}

			if ($this->exportedOnly && !$project->GetDaemonEnabled()) {
				$this->Log(sprintf('Project %1$s not enabled for export', $project->GetPath()));
				return null;
			}

			$this->ApplyGlobalConfig($project);

			$this->ApplyGitConfig($project);

			if ($this->projectSettings && isset($this->projectSettings[$proj])) {
				$this->ApplyProjectSettings($project, $this->projectSettings[$proj]);
			}

			$this->InjectProjectDependencies($project);

			return $project;

		} catch (Exception $e) {
			$this->Log($e->getMessage());
		}

		return null;
	}

	/**
	 * Save and restore project list to prevent parsing directories
	 */
	public function CacheSaveProjectList()
	{
		$data = serialize($this->projects);
		if (!is_file(self::CACHE_PROJECTLIST) || $data != file_get_contents(self::CACHE_PROJECTLIST)) {
			return (file_put_contents(self::CACHE_PROJECTLIST,$data) > 0);
		}
		return true;
	}

}
