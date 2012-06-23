<?php
/**
 * Lists all projects in a multidimensional array
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectListArray extends GitPHP_ProjectListBase
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
			throw new Exception('An array of projects is required');
		}

		$this->projectConfig = $projectArray;

		parent::__construct($projectRoot);
	}

	/**
	 * Populates the internal list of projects
	 */
	protected function PopulateProjects()
	{
		foreach ($this->projectConfig as $proj => $projData) {
			try {
				if (is_string($projData)) {
					// Just flat array of project paths
					$projObj = $this->InstantiateProject($projData);
					if ($projObj) {
						$this->projects[$projData] = $projObj;
						unset($projObj);
					}
				} else if (is_array($projData)) {
					if (is_string($proj) && !empty($proj)) {
						// Project key pointing to data array
						$projObj = $this->InstantiateProject($proj);
						if ($projObj) {
							$this->projects[$proj] = $projObj;
							unset($projObj);
						}
					} else if (isset($projData['project'])) {
						// List of data arrays with projects inside
						$projObj = $this->InstantiateProject($projData['project']);
						if ($projObj) {
							$this->projects[$projData['project']] = $projObj;
							unset($projObj);
						}
					}
				}
			} catch (Exception $e) {
				GitPHP_DebugLog::GetInstance()->Log($e->getMessage());
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
		$projectSettings = null;
		foreach ($this->projectConfig as $key => $projData) {
			if (is_string($projData) && ($projData == $proj)) {
				// Just flat array of project paths
				$found = true;
				break;
			} else if (is_array($projData)) {
				if (is_string($key) && !empty($key) && ($key == $proj)) {
					// Project key pointing to data array
					$found = true;
					$projectSettings = $projData;
					break;
				}
				if (isset($projData['project']) && ($projData['project'] == $proj)) {
					// List of data arrays with projects inside
					$found = true;
					$projectSettings = $projData;
					break;
				}
			}
		}

		if (!$found) {
			return;
		}

		$projectObj = new GitPHP_Project($this->projectRoot, $proj);

		$this->InjectProjectDependencies($projectObj);

		$this->ApplyGlobalConfig($projectObj);

		$this->ApplyGitConfig($projectObj);

		if ($projectSettings != null)
			$this->ApplyProjectSettings($projectObj, $projectSettings);

		if ($this->projectSettings && isset($this->projectSettings[$proj])) {
			$this->ApplyProjectSettings($projectObj, $this->projectSettings[$proj]);
		}

		return $projectObj;
	}

}
