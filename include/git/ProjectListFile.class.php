<?php
/**
 * GitPHP ProjectListFile
 *
 * Lists all projects in a given file
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
 * ProjectListFile class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListFile extends GitPHP_ProjectListBase
{

	/**
	 * fileContents
	 *
	 * Stores the contents of the project list file
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
		if (!$this->fileRead)
			$this->ReadFile();

		foreach ($this->fileContents as $lineData) {
			if (isset($lineData['project'])) {
				$projObj = $this->InstantiateProject($lineData['project']);
				if ($projObj) {
					$this->projects[$lineData['project']] = $projObj;
					unset($projObj);
				}
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
					GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projectRoot . $proj));
				}
				break;
			}
		}

		if (!$found)
			return null;

		$projectObj = new GitPHP_Project($this->projectRoot, $proj);

		$this->ApplyGitConfig($projectObj);

		if (!empty($owner))
			$projectObj->SetOwner($owner);

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
	 * @access protected
	 */
	protected function ReadFile()
	{
		$this->fileRead = true;

		$fileString = file_get_contents($this->projectConfig);
		
		if ($fileString === false) {
			throw new Exception(sprintf(__('Failed to open project list file %1$s'), $this->projectConfig));
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
