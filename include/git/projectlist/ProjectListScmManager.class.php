<?php
/**
 * GitPHP ProjectListScmManager
 *
 * Lists all projects in an scm-manager config file
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * ProjectListScmManager class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListScmManager extends GitPHP_ProjectListBase
{
	/**
	 * fileContents
	 *
	 * Stores the contents of the project config file
	 *
	 * @access protected
	 */
	protected $fileContents = array();

	/**
	 * fileRead
	 *
	 * Stores whether the file has been read
	 *
	 * @access protected
	 */
	protected $fileRead = false;
	
	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectRoot project root
	 * @param string $projectFile file to read
	 * @throws Exception if parameter is not a readable file
	 * @access public
	 */
	public function __construct($projectRoot, $projectFile)
	{
		if (!(is_string($projectFile) && is_file($projectFile))) {
			throw new Exception(sprintf(__('%1$s is not a file'), $projectFile));
		}

		$this->projectConfig = $projectFile;

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
		if (!$this->fileRead)
			$this->ReadFile();

		foreach ($this->fileContents as $projData) {
			$projObj = $this->InstantiateProject($projData['name']);
			if ($projObj) {
				$this->projects[$projData['name']] = $projObj;
				unset($projObj);
			}
		}
	}

	/**
	 * InstantiateProject
	 *
	 * Instantiates the project object
	 *
	 * @access protected
	 * @param string $proj project
	 * @return mixed project object
	 */
	protected function InstantiateProject($proj)
	{
		if (!$this->fileRead)
			$this->ReadFile();

		$data = null;
		$found = false;

		foreach ($this->fileContents as $projData) {
			if (isset($projData) && ($proj == $projData['name'])) {
				$data = $projData;
				$found = true;
				break;
			}
		}

		if (!$found)
			return null;

		if (!(isset($data['type']) && ($data['type'] == 'git'))) {
			GitPHP_DebugLog::GetInstance()->Log(sprintf('%1$s is not a git project', $proj));
			return null;
		}

		if (!(isset($data['public']) && ($data['public'] == true))) {
			GitPHP_DebugLog::GetInstance()->Log(sprintf('%1$s is not public', $proj));
			return null;
		}

		if (!is_file(GitPHP_Util::AddSlash($this->projectRoot) . $proj . '/HEAD')) {
			GitPHP_DebugLog::GetInstance()->Log(sprintf('%1$s is not a git project', $proj));
		}

		$projectObj = new GitPHP_Project($this->projectRoot, $proj);

		$this->ApplyGlobalConfig($projectObj);

		$this->ApplyGitConfig($projectObj);

		if (isset($data['owner']) && !empty($data['owner'])) {
			$projectObj->SetOwner($data['owner']);
		}

		if (isset($data['description']) && !empty($data['description'])) {
			$projectObj->SetDescription($data['description']);
		}

		if ($this->projectSettings && isset($this->projectSettings[$proj])) {
			$this->ApplyProjectSettings($projectObj, $this->projectSettings[$proj]);
		}

		return $projectObj;
	}

	/**
	 * ReadFile
	 *
	 * Reads the file contents
	 *
	 * @access private
	 */
	protected function ReadFile()
	{
		$this->fileRead = true;

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($this->projectConfig);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml) {
			throw new Exception(sprintf('Could not load SCM manager config %1$s', $this->projectConfig));
		}

		foreach ($xml->repositories->repository as $repository) {
			
			$name = trim($repository->name);
			if (empty($name))
				continue;

			$data = array();
			$data['name'] = $name;
			$data['type'] = $repository->type;
			$data['public'] = ($repository->public == 'true');
			
			$owner = trim($repository->contact);
			if (!empty($owner))
				$data['owner'] = $owner;

			$description = trim($repository->description);
			if (!empty($description))
				$data['description'] = $description;

			$this->fileContents[] = $data;

		}
	}

	/**
	 * IsSCMManager
	 *
	 * Tests if this file is an SCM manager config file
	 *
	 * @access protected
	 * @returns true if file is an SCM manager config
	 */
	public static function IsSCMManager($file)
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

		if ($xml->getName() !== 'repository-db')
			return false;

		return true;
	}

}
