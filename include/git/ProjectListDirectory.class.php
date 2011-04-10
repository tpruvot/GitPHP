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
		$this->repoSupport = $Config->GetValue('reposupport', false);
		$this->sublevels   = $Config->GetValue('subfolder_levels', 0);

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
		$this->curlevel = 0;
		$this->RecurseDir($this->projectDir);
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
		if (!is_dir($dir))
			return;

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
					try {
						$proj = new GitPHP_Project($projectPath);
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
				}

			}
			closedir($dh);
		}
	}

}
