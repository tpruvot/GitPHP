<?php
/**
 * GitPHP ProjectListArrayLegacy
 *
 * Lists all projects in a multidimensional array
 * Legacy array format
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

define('GITPHP_NO_CATEGORY', 'none');

/**
 * ProjectListArrayLegacy class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListArrayLegacy extends GitPHP_ProjectListBase
{

	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectRoot project root
	 * @param mixed $projectArray array to read
	 * @throws Exception if parameter is not an array
	 * @access public
	 */
	public function __construct($projectRoot, $projectArray)
	{
		if (!is_array($projectArray)) {
			throw new Exception('An array of projects is required.');
		}

		$this->projectConfig = $projectArray;

		parent::__construct($projectRoot);
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
		foreach ($this->projectConfig as $cat => $plist) {
			if (is_array($plist)) {
				foreach ($plist as $pname => $ppath) {
					try {
						$projObj = $this->InstantiateProject($ppath);
						if ($projObj) {
							$this->projects[$ppath] = $projObj;
							unset($projObj);
						}
					} catch (Exception $e) {
						GitPHP_Log::GetInstance()->Log($e->getMessage());
					}
				}
			}
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
		$found = false;
		$projectCat = GITPHP_NO_CATEGORY;
		foreach ($this->projectConfig as $cat => $plist) {
			if (is_array($plist) && (array_search($proj, $plist) !== false)) {
				$found = true;
				$projectCat = $cat;
				break;
			}
		}

		if (!$found) {
			return;
		}

		$projectObj = new GitPHP_Project($this->projectRoot, $proj);

		$this->ApplyGlobalConfig($projectObj);

		$this->ApplyGitConfig($projectObj);

		if ($projectCat != GITPHP_NO_CATEGORY)
			$projectObj->SetCategory($projectCat);

		return $projectObj;
	}

}
