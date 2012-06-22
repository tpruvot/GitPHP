<?php
/**
 * Constant for no category indicator
 */
define('GITPHP_NO_CATEGORY', 'none');

/**
 * Lists all projects in a multidimensional array (Legacy array format)
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectListArrayLegacy extends GitPHP_ProjectListBase
{

	/**
	 * constructor
	 *
	 * @param string $projectRoot project root
	 * @param mixed $projectArray array to read
	 * @throws Exception if parameter is not an array
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
	 * Populates the internal list of projects
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
						GitPHP_DebugLog::GetInstance()->Log($e->getMessage());
					}
				}
			}
		}
	}

	/**
	 * Instantiates project object
	 *
	 * @param string $proj project
	 * @return GitPHP_Project project
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
