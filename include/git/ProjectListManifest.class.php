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

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * GitPHP_ProjectListManifest class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListManifest extends GitPHP_ProjectListBase
{

	protected $remotes=array();

	protected $default=array();

	protected $local_manifest='';

	protected $removes=array();

	protected $local_projects=array();

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
		//remotes list to associative array
		$remotes = array();
		foreach ($xml->remote as $k => $remote) {
			$remoteName = (string) $remote['name'];
			$remotes[$remoteName] = (array) $remote;
			$remotes[$remoteName] = $remotes[$remoteName]['@attributes'];
		}
		$this->remotes = $remotes;

		//projects
		$projects = array();
		foreach ($xml->project as $k => $node) {
			$project = (string) $node['name'];
			$projects[$project] = $node;
		}

		//default branch/tag (revision attribute)
		//        remote (remote attribute)
		$this->default = $xml->default;

		$local_manifest = str_replace('/manifest.xml','/local_manifest.xml',$this->projectConfig);
		if (is_file($local_manifest)) {
			$this->local_manifest = $local_manifest;
			if ($this->IncludeLocalManifest($xml)) {

				foreach ($this->removes as $project => $b) {
					if (array_key_exists($project,$projects)) {
						GitPHP_Log::GetInstance()->Log(sprintf('remove project %1$s',$project));
						$projects[$project] = NULL;
					}
				}

				foreach ($this->local_projects as $project => $node) {
					GitPHP_Log::GetInstance()->Log(sprintf('override project %1$s',$project));
					$projects[$project] = $node;
				}

			}
		}

		GitPHP_Config::GetInstance()->SetValue('reposupport', true);

		foreach ($projects as $project) {

			//deleted projects
			if (empty($project)) continue;

			$repository = array();
			$repository['path'] = (string) $project['path'];

			$projPath = trim($repository['path']);
			if (empty($projPath)) continue;

			$repository['name'] = (string) $project['name'];
			$repository['revision'] = (empty($project['revision'])) ? (string) $this->default['revision'] : (string) $project['revision'];
			$repository['remote']   = (empty($project['remote'])) ?   (string) $this->default['remote']   : (string) $project['remote'];

			$projPath .= '.git';

			if (!strstr($projectRoot,'.repo'))
				$projectRoot = $projectRoot . '.repo/projects/';

			$fullPath = $projectRoot . $projPath;
			if (!is_file($fullPath . '/HEAD')) {
				GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projPath));
			} else {
				try {
					$projectPath = substr($fullPath, strlen($projectRoot));

					$projObj = new GitPHP_Project($projectRoot, $projectPath);
					$projObj->isAndroidRepo = true;

					$remoteName = $repository['remote'];
					$projObj->repoRemote = $remoteName;

					//revision can be a tag, ignore it
					if (strpos($repository['revision'],'/tags/') === false)
						$projObj->repoBranch = $repository['revision'];

					$projOwner = $repository['name'];
					if (!empty($projOwner)) {
						if (strpos($projOwner,'/') > 0)
							$projOwner = substr($projOwner,0,strpos($projOwner,'/'));
						$projObj->SetOwner($projOwner);
						$projObj->SetCategory($remoteName.' - '.$projOwner);
					}

					$projObj->SetDescription($remoteName.':'.$repository['name']);

					//remote url + project name
					$remoteUrl = @ $remotes[$remoteName]['fetch'];
					if (!empty($remoteUrl)) {
						$remoteUrl .= $repository['name'].'.git';
						$projObj->SetCloneUrl($remoteUrl);
					}

					//gerrit
					$remoteUrl = @ $remotes[$remoteName]['review'];
					if (!empty($remoteUrl)) {
						$remoteUrl = 'http://'.$remoteUrl.'/#q,project:'.$repository['name'];
						$projObj->SetBugUrl($remoteUrl);
					}

					$this->projects[$projPath] = $projObj;

				} catch (Exception $e) {
					GitPHP_Log::GetInstance()->Log($e->getMessage());
				}
			}
		}
		GitPHP_Log::GetInstance()->Log(sprintf('Found %1$d projects in manifest(s)', count($projects)));
	}

	/**
	 * IncludeLocalManifest
	 *
	 * load the local_manifest.xml if present
	 *
	 * @access private
	 * @returns true if done
	 */
	private function IncludeLocalManifest($main_xml)
	{
		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($this->local_manifest);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml)
			return false;

		if ($xml->getName() !== 'manifest')
			return false;

		GitPHP_Log::GetInstance()->Log(sprintf('Found a local_manifest.xml'));

		//remove-project tags
		$removes = array();
		foreach ($xml->{'remove-project'} as $k => $node) {
			$project = (string) $node['name'];
			$removes[$project] = true;
		}
		$this->removes = $removes;

		//local projects
		$projects = array();
		foreach ($xml->project as $k => $node) {
			$project = (string) $node['name'];
			$projects[$project] = $node;
		}
		$this->local_projects = $projects;

		GitPHP_Log::GetInstance()->Log(sprintf('Found %1$d projects in local manifest', count($projects)));

		return true;
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
