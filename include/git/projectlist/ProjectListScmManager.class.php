<?php
/**
 * Lists all projects in an scm-manager config file
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectListScmManager extends GitPHP_ProjectListBase
{
	/**
	 * The contents of the project config file
	 *
	 * @var array
	 */
	protected $fileContents = array();

	/**
	 * Whether the file has been read
	 *
	 * @var boolean
	 */
	protected $fileRead = false;
	
	/**
	 * constructor
	 *
	 * @param string $projectRoot project root
	 * @param string $projectFile file to read
	 * @throws Exception if parameter is not a readable file
	 */
	public function __construct($projectRoot, $projectFile)
	{
		if (!(is_string($projectFile) && is_file($projectFile))) {
			throw new GitPHP_InvalidFileException($projectFile);
		}

		$this->projectConfig = $projectFile;

		parent::__construct($projectRoot);
	}

	/**
	 * Populates the internal list of projects
	 */
	protected function PopulateProjects()
	{
		if (!$this->fileRead)
			$this->ReadFile();

		foreach ($this->fileContents as $projData) {
			$projObj = $this->LoadProject($projData['name']);
			if ($projObj) {
				$this->projects[$projData['name']] = $projObj;
				unset($projObj);
			}
		}
	}

	/**
	 * Loads a project
	 *
	 * @param string $proj project
	 * @return GitPHP_Project project object
	 */
	protected function LoadProject($proj)
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
			$this->Log('Invalid project', $proj);
			return null;
		}

		if (!(isset($data['public']) && ($data['public'] == true))) {
			$this->Log('Private project', $proj);
			return null;
		}

		if (!is_file(GitPHP_Util::AddSlash($this->projectRoot) . $proj . '/HEAD')) {
			$this->Log('Invalid project', $proj);
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

		$this->InjectProjectDependencies($projectObj);

		return $projectObj;
	}

	/**
	 * Reads the file contents
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
	 * Tests if this file is an SCM manager config file
	 *
	 * @param string $file file
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
