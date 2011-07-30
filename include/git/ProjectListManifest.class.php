<?php
/**
 * GitPHP ProjectListManifest
 *
 * Lists all projects in an .repo manifest.xml file
 *
 * @author Tanguy Pruvot
 * @package GitPHP
 * @subpackage Git
 */

/* TODO :

  <remove-project name="sdk/monkeyrunner"/>

*/

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * ProjectListScmManager class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListManifest extends GitPHP_ProjectListBase
{
	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectFile file to read
	 * @throws Exception if parameter is not a readable file
	 * @access public
	 */
	public function __construct($projectFile)
	{
		if (!(is_string($projectFile) && is_file($projectFile))) {
			throw new Exception(sprintf(__('%1$s is not a file'), $projectFile));
		}

		$this->projectConfig = $projectFile;

		parent::__construct();
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 * @throws Exception if file cannot be read
	 */
	protected function PopulateProjects()
	{
		$projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($this->projectConfig);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml) {
			throw new Exception(sprintf('Could not load Manifest %1$s', $this->projectConfig));
		}

		//remotes list
		$remotes = $xml->remote;

		//default branch/tag (revision attribute)
		//        remote (remote attribute)
		$default = $xml->default;

		foreach ($xml->project as $project) {

			GitPHP_Config::GetInstance()->SetValue('exportedonly', false);

			$repository = array();
			$repository['path'] = (string) $project['path'];
			$repository['name'] = (string) $project['name'];
			$repository['revision'] = (empty($project['revision'])) ? (string) $default['revision'] : (string) $project['revision'];
			$repository['remote']   = (empty($project['remote'])) ? (string) $default['remote'] : (string) $project['remote'];

			$projPath = trim($repository['path']);
			if (empty($projPath))
				continue;

			$projPath .= '.git';

			if (!strstr($projectRoot,'.repo'))
				$projectRoot = $projectRoot . '.repo/projects/';

			$fullPath = $projectRoot . $projPath;
			if (is_file($fullPath . '/HEAD')) {
				try {
					$projectPath = substr($fullPath, strlen($projectRoot));
					GitPHP_Log::GetInstance()->Log(sprintf('Found project %1$s', $projectPath));

					$projObj = new GitPHP_Project($projectRoot, $projectPath);

					$projOwner = trim($repository['name']);
					if (!empty($projOwner)) {
						if (strpos($projOwner,'/') > 0)
							$projOwner = substr($projOwner,0,strpos($projOwner,'/'));
						$projObj->SetOwner($projOwner);
						$projObj->SetCategory($repository['remote'].' - '.$projOwner);
					}

					$projObj->SetDescription($repository['remote'].':'.$repository['name']);

					$this->projects[$projPath] = $projObj;

				} catch (Exception $e) {
					GitPHP_Log::GetInstance()->Log($e->getMessage());
				}
			} else {
				GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projName));
			}
		}
	}

	/**
	 * IsRepoManifest
	 *
	 * Tests if this file is a valid Manifest file
	 *
	 * @access public
	 * @returns true if file is a Manifest
	 */
	public static function IsRepoManifest($file)
	{
		if (empty($file))
			return false;

		if (!(is_string($file) && is_file($file)))
			return false;

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($file);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml)
			return false;

		if ($xml->getName() !== 'manifest')
			return false;

		return true;
	}

}
