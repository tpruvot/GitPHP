<?php
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
	 * Whether to only list exported projects
	 *
	 * @var boolean
	 */
	protected $exportedOnly = false;

	/**
	 * Constructor
	 *
	 * @param string $projectRoot project root
	 * @param bool $exportedOnly whether to only allow exported projects
	 */
	public function __construct($projectRoot, $exportedOnly = false)
	{
		$this->exportedOnly = $exportedOnly;

		parent::__construct($projectRoot);

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
		$this->RecurseDir(GitPHP_Util::AddSlash($this->projectRoot));
	}

	/**
	 * Recursively searches for projects
	 *
	 * @param string $dir directory to recurse into
	 */
	private function RecurseDir($dir)
	{
		if (!(is_dir($dir) && is_readable($dir)))
			return;

		$this->Log('Search directory', $dir);

		if ($dh = opendir($dir)) {
			$trimlen = strlen(GitPHP_Util::AddSlash($this->projectRoot)) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath)) {
					if (is_file($fullPath . '/HEAD')) {
						$this->Log('Found project', $fullPath);
						$projectPath = substr($fullPath, $trimlen);
						if (!isset($this->projects[$projectPath])) {
							$project = $this->LoadProject($projectPath);
							if ($project) {
								$this->projects[$projectPath] = $project;
								unset($project);
							}
						}
					} else {
						$this->RecurseDir($fullPath);
					}
				} else {
					$this->Log('Skip', $fullPath);
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
				$this->Log('Project export disabled', $project->GetPath());
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
			$this->Log('Project error', $e->getMessage());
		}

		return null;
	}

}
