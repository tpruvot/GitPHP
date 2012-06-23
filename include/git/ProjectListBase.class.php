<?php
/**
 * Base class that all projectlist classes extend
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_ProjectListBase implements Iterator, GitPHP_Observable_Interface
{

	/**
	 * Sort constants
	 */
	const SORT_PROJECT = 'project';
	const SORT_DESCRIPTION = 'descr';
	const SORT_OWNER = 'owner';
	const SORT_AGE = 'age';

	/**
	 * Stores array of projects internally
	 */
	protected $projects;

	/**
	 * Stores whether the list of projects has been loaded
	 */
	protected $projectsLoaded = false;

	/**
	 * Stores the project configuration internally
	 */
	protected $projectConfig = null;

	/**
	 * Stores the project settings internally
	 */
	protected $projectSettings = null;

	/**
	 * Stores the project root internally
	 */
	protected $projectRoot = null;

	/**
	 * Executable for all projects
	 */
	protected $exe = null;

	/**
	 * Config provider
	 */
	protected $config = null;

	/**
	 * @var GitPHP_Observer_Interface[]
	 */
	protected $observers = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->projects = array();
		$this->projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));
		if (empty($this->projectRoot)) {
			throw new GitPHP_MessageException(__('A projectroot must be set in the config'), true, 500);
		}
		if (!$this->IsDir($this->projectRoot)) {
			throw new Exception(sprintf(__('%1$s is not a directory'), $this->projectRoot));
		}

	}

	/**
	 * Get config provider
	 */
	public function GetConfig()
	{
		return $this->config;
	}

	/**
	 * Set config provider
	 */
	public function SetConfig($config)
	{
		$this->config = $config;
	}

	/**
	 * Get executable
	 */
	public function GetExe()
	{
		return $this->exe;
	}

	/**
	 * Set executable
	 */
	public function SetExe($exe)
	{
		$this->exe = $exe;
	}

	/**
	 * @return boolean true if folder or a link pointing to a folder
	 * @param string path to check
	 */
	protected function IsDir($dir)
	{
		return is_dir($dir) || (is_link($dir) && is_dir("$dir/."));
	}

	/**
	 * Test if the projectlist contains
	 * the given project
	 *
	 * @return boolean true if project exists in list
	 * @param string $project the project string to find
	 */
	public function HasProject($project)
	{
		if (empty($project))
			return false;

		return isset($this->projects[$project]);
	}

	/**
	 * Gets a particular project
	 *
	 * @return mixed project object or null
	 * @param string $project the project to find
	 */
	public function GetProject($project)
	{
		if (empty($project))
			return null;

		if (isset($this->projects[$project]))
			return $this->projects[$project];

		if (!$this->projectsLoaded) {
			$projObj = $this->InstantiateProject($project);
			$this->projects[$project] = $projObj;
			return $projObj;
		}

		return null;
	}

	/**
	 * Instantiates a project object
	 *
	 * @param string $proj project
	 * @return mixed project object
	 */
	protected function InstantiateProject($proj)
	{
		$project = new GitPHP_Project(GitPHP_Util::AddSlash($this->projectRoot), $proj);

		if ($this->projectSettings && isset($this->projectSettings[$proj])) {
			$this->ApplyProjectSettings($project, $this->projectSettings[$proj]);
		}

		return $project;
	}

	/**
	 * Gets the config defined for this ProjectList
	 */
	public function GetProjectListConfig()
	{
		return $this->projectConfig;
	}

	/**
	 * Gets the settings applied to this projectlist
	 */
	public function GetProjectSettings()
	{
		return $this->projectSettings;
	}

	/**
	 * Loads all projects in the list
	 */
	public function LoadProjects()
	{
		$this->PopulateProjects();

		$this->projectsLoaded = true;

		$this->Sort();

		$this->ApplySettings();
	}

	/**
	 * Populates the internal list of projects
	 */
	abstract protected function PopulateProjects();

	/**
	 * Rewinds the iterator
	 */
	function rewind()
	{
		return reset($this->projects);
	}

	/**
	 * Returns the current element in the array
	 */
	function current()
	{
		return current($this->projects);
	}

	/**
	 * Returns the current key
	 */
	function key()
	{
		return key($this->projects);
	}

	/**
	 * Advance the pointer
	 */
	function next()
	{
		return next($this->projects);
	}

	/**
	 * Test for a valid pointer
	 */
	function valid()
	{
		return key($this->projects) !== null;
	}

	/**
	 * Sorts the project list
	 * @param string $sortBy sort method
	 */
	public function Sort($sortBy = self::SORT_PROJECT)
	{
		switch ($sortBy) {
			case self::SORT_DESCRIPTION:
				uasort($this->projects, array('GitPHP_Project', 'CompareDescription'));
				break;
			case self::SORT_OWNER:
				uasort($this->projects, array('GitPHP_Project', 'CompareOwner'));
				break;
			case self::SORT_AGE:
				$this->GetCategoryAges();
				uasort($this->projects, array('GitPHP_Project', 'CompareAge'));
				break;
			case self::SORT_PROJECT:
			default:
				uasort($this->projects, array('GitPHP_Project', 'CompareProject'));
				break;
		}
	}

	/**
	 * Gets the count of projects
	 *
	 * @return integer number of projects
	 */
	public function Count()
	{
		return count($this->projects);
	}

	/**
	 * Returns a filtered list of projects
	 *
	 * @param string $filter filter pattern
	 * @return array array of filtered projects
	 */
	public function Filter($pattern = null)
	{
		if (empty($pattern))
			return $this->projects;

		$matches = array();

		foreach ($this->projects as $proj) {
			if ((stripos($proj->GetProject(), $pattern) !== false) ||
			    (stripos($proj->GetDescription(), $pattern) !== false) ||
			    (stripos($proj->GetOwner(), $pattern) !== false)) {
			    	$matches[] = $proj;
			}
		}

		return $matches;
	}

	/**
	 * Store project category age
	 */
	protected function GetCategoryAges()
	{
		foreach ($this->projects as $proj) {
			$cat = $proj->GetCategory('none');
			if (isset($ages[$cat]))
				$ages[$cat] = min($ages[$cat], $proj->GetAge());
			else
				$ages[$cat] = $proj->GetAge();
		}
		foreach ($this->projects as $proj) {
			$cat = $proj->GetCategory('none');
			$proj->categoryAge = $ages[$cat];
		}
	}

	/**
	 * Applies override settings for a project
	 *
	 * @param string $project the project object
	 * @param array $projData project data array
	 */
	protected function ApplyProjectSettings($project, $projData)
	{
		if (!$project)
			return;

		if (isset($projData['category']) && is_string($projData['category'])) {
			$project->SetCategory($projData['category']);
		}
		if (isset($projData['owner']) && is_string($projData['owner'])) {
			$project->SetOwner($projData['owner']);
		}
		if (isset($projData['description']) && is_string($projData['description'])) {
			$project->SetDescription($projData['description']);
		}
		if (isset($projData['cloneurl']) && is_string($projData['cloneurl'])) {
			$project->SetCloneUrl($projData['cloneurl']);
		}
		if (isset($projData['pushurl']) && is_string($projData['pushurl'])) {
			$project->SetPushUrl($projData['pushurl']);
		}
		if (isset($projData['bugpattern']) && is_string($projData['bugpattern'])) {
			$project->SetBugPattern($projData['bugpattern']);
		}
		if (isset($projData['bugurl']) && is_string($projData['bugurl'])) {
			$project->SetBugUrl($projData['bugurl']);
		}
		if (isset($projData['compat'])) {
			$project->SetCompat($projData['compat']);
		}
		if (isset($projData['website']) && is_string($projData['website'])) {
			$project->SetWebsite($projData['website']);
		}
	}

	/**
	 * Sets a list of settings for the project list
	 *
	 * @param array $settings the array of settings
	 */
	public function SetProjectSettings($settings)
	{
		if ((!$settings) || (count($settings) < 1))
			return;

		$this->projectSettings = $settings;

		$this->ApplySettings();
	}

	/**
	 * Applies project settings to project list
	 */
	protected function ApplySettings()
	{
		if (!$this->projectSettings)
			return;

		if (count($this->projects) > 0) {
			foreach ($this->projectSettings as $proj => $setting) {

				if (empty($proj)) {
					if (isset($setting['project']) && !empty($setting['project'])) {
						$proj = $setting['project'];
					}
				}

				if (!isset($this->projects[$proj]))
					continue;

				$this->ApplyProjectSettings($this->projects[$proj], $setting);
			}
		}
	}

	/**
	 * Add a new observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function AddObserver($observer)
	{
		if (!$observer)
			return;
		if (array_search($observer, $this->observers) !== false)
			return;

		$this->observers[] = $observer;
	}

	/**
	 * Remove an observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function RemoveObserver($observer)
	{
		if (!$observer)
			return;

		$key = array_search($observer, $this->observers);

		if ($key === false)
			return;

		unset($this->observers[$key]);
	}

	/**
	 * Log a message to observers
	 *
	 * @param string $message message
	 */
	protected function Log($message)
	{
		if (empty($message))
			return;

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::LoggableChange, array($message));
		}
	}
}
