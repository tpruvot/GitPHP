<?php
/**
 * Lists all projects in a given file
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectListFile extends GitPHP_ProjectListBase
{

	/**
	 * The contents of the project list file
	 *
	 * @var string[]
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

		foreach ($this->fileContents as $lineData) {
			if (isset($lineData['project'])) {
				$projObj = $this->LoadProject($lineData['project']);
				if ($projObj) {
					$this->projects[$lineData['project']] = $projObj;
					unset($projObj);
				}
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

		$found = false;
		$owner = null;
		foreach ($this->fileContents as $lineData) {
			if (isset($lineData['project']) && ($lineData['project'] == $proj)) {
				$projectRoot = GitPHP_Util::AddSlash($this->projectRoot);
				if (is_file($projectRoot . $proj . '/HEAD')) {
					$found = true;
					if (isset($lineData['owner'])) {
						$owner = $lineData['owner'];
					}
				} else {
					$this->Log('Invalid project', $projectRoot . $proj);
				}
				break;
			}
		}

		if (!$found)
			return null;

		$projectObj = new GitPHP_Project($this->projectRoot, $proj);

		$this->ApplyGlobalConfig($projectObj);

		$this->ApplyGitConfig($projectObj);

		if (!empty($owner))
			$projectObj->SetOwner($owner);

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

		$fileString = file_get_contents($this->projectConfig);
		
		if ($fileString === false) {
			throw new GitPHP_ProjectListFileReadException($this->projectConfig);
		}

		$this->fileContents = array();

		$fileLines = explode("\n", $fileString);
		foreach ($fileLines as $line) {
			if (preg_match('/^([^\s]+)(\s.+)?$/', $line, $regs)) {
				$data = array();
				$data['project'] = $regs[1];
				$owner = trim($regs[2]);
				if (!empty($owner)) {
					$data['owner'] = $owner;
				}
				$this->fileContents[] = $data;
			}
		}
	}

}
