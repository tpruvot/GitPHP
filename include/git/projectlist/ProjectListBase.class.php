<?php
/**
 * Base class that all projectlist classes extend
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
abstract class GitPHP_ProjectListBase implements Iterator, GitPHP_Observable_Interface
{
	/**
	 * Project name sort
	 *
	 * @var string
	 */
	const ProjectSort = 'project';

	/**
	 * Project description sort
	 *
	 * @var string
	 */
	const DescriptionSort = 'descr';

	/**
	 * Project owner sort
	 *
	 * @var string
	 */
	const OwnerSort = 'owner';

	/**
	 * Project age sort
	 *
	 * @var string
	 */
	const AgeSort = 'age';

	/**
	 * Project list
	 *
	 * @var GitPHP_Project[]
	 */
	protected $projects;

	/**
	 * Whether the list of projects has been loaded
	 *
	 * @var boolean
	 */
	protected $projectsLoaded = false;

	/**
	 * The projectlist configuration
	 *
	 * @var string
	 */
	protected $projectConfig = null;

	/**
	 * Project settings
	 *
	 * @var array
	 */
	protected $projectSettings = null;

	/**
	 * The project root
	 *
	 * @var string
	 */
	protected $projectRoot = null;

	/**
	 * Object cache instance for all projects
	 *
	 * @var GitPHP_Cache
	 */
	protected $cache = null;

	/**
	 * Memory cache instance for all projects
	 *
	 * @var GitPHP_MemoryCache
	 */
	protected $memoryCache = null;

	/**
	 * Executable for all projects
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe = null;

	/**
	 * Config provider
	 *
	 * @var GitPHP_Config
	 */
	protected $config = null;

	/**
	 * Observers
	 *
	 * @var GitPHP_Observer_Interface[]
	 */
	protected $observers = array();

	/**
	 * Constructor
	 *
	 * @param string $projectRoot project root
	 */
	public function __construct($projectRoot)
	{
		$this->projects = array();
		$this->projectRoot = GitPHP_Util::AddSlash($projectRoot);
		if (empty($this->projectRoot)) {
			throw new GitPHP_MissingProjectrootException();
		}
		if (!is_dir($this->projectRoot)) {
			throw new GitPHP_InvalidDirectoryConfigurationException($this->projectRoot);
		}
	}

	/**
	 * Get config provider
	 *
	 * @return GitPHP_Config
	 */
	public function GetConfig()
	{
		return $this->config;
	}

	/**
	 * Set config provider
	 *
	 * @param GitPHP_Config $config config provider
	 */
	public function SetConfig($config)
	{
		$this->config = $config;
	}

	/**
	 * Get memory cache instance
	 *
	 * @return GitPHP_MemoryCache|null
	 */
	public function GetMemoryCache()
	{
		return $this->memoryCache;
	}

	/**
	 * Set memory cache instance
	 *
	 * @param GitPHP_MemoryCache|null $memoryCache memory cache instance
	 */
	public function SetMemoryCache($memoryCache)
	{
		$this->memoryCache = $memoryCache;
	}

	/**
	 * Get object cache instance
	 *
	 * @return GitPHP_Cache|null object cache
	 */
	public function GetCache()
	{
		return $this->cache;
	}

	/**
	 * Set object cache instance
	 *
	 * @param GitPHP_Cache|null $cache object cache instance
	 */
	public function SetCache($cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Get executable
	 *
	 * @return GitPHP_GitExe executable
	 */
	public function GetExe()
	{
		return $this->exe;
	}

	/**
	 * Set executable
	 *
	 * @param GitPHP_GitExe $exe executable
	 */
	public function SetExe($exe)
	{
		$this->exe = $exe;
	}

	/**
	 * Test if the projectlist contains the given project
	 *
	 * @return boolean true if project exists in list
	 * @param string $project the project to find
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
	 * @return GitPHP_Project|null project object or null
	 * @param string $project the project to find
	 */
	public function GetProject($project)
	{
		if (empty($project))
			return null;

		if (isset($this->projects[$project]))
			return $this->projects[$project];

		if (!$this->projectsLoaded) {
			$projObj = $this->LoadProject($project);
			$this->projects[$project] = $projObj;
			return $projObj;
		}

		return null;
	}

	/**
	 * Loads a project
	 *
	 * @param string $proj project
	 * @return return GitPHP_Project project object
	 */
	protected function LoadProject($proj)
	{
		$project = new GitPHP_Project(GitPHP_Util::AddSlash($this->projectRoot), $proj);

		$this->ApplyGlobalConfig($project);

		$this->ApplyGitConfig($project);

		if ($this->projectSettings && isset($this->projectSettings[$proj])) {
			$this->ApplyProjectSettings($project, $this->projectSettings[$proj]);
		}

		$this->InjectProjectDependencies($project);

		return $project;
	}

	/**
	 * Inject project dependency objects
	 *
	 * @param GitPHP_Project $project project object
	 */
	protected function InjectProjectDependencies($project)
	{
		if (!$project)
			return;

		$compat = $project->GetCompat();

		$loader = null;
		if ($compat) {
			$project->SetStrategy(new GitPHP_ProjectLoad_Git($this->exe));
		} else {
			$loader = new GitPHP_GitObjectLoader($project);
			$project->SetStrategy(new GitPHP_ProjectLoad_Raw($loader));
		}

		$headListStrategy = null;
		if ($compat) {
			$headListStrategy = new GitPHP_HeadListLoad_Git($this->exe);
		} else {
			$headListStrategy = new GitPHP_HeadListLoad_Raw();
		}
		$headList = new GitPHP_HeadList($project, $headListStrategy);
		$project->SetHeadList($headList);

		$tagListStrategy = null;
		if ($compat) {
			$tagListStrategy = new GitPHP_TagListLoad_Git($this->exe);
		} else {
			$tagListStrategy = new GitPHP_TagListLoad_Raw();
		}
		$tagList = new GitPHP_TagList($project, $tagListStrategy);
		$project->SetTagList($tagList);

		$manager = new GitPHP_GitObjectManager($project);
		$manager->SetCompat($compat);
		if (!$compat) {
			$manager->SetObjectLoader($loader);
		}
		$manager->SetExe($this->exe);
		if ($this->memoryCache) {
			$manager->SetMemoryCache($this->memoryCache);
		}
		if ($this->cache) {
			$manager->SetCache($this->cache);
		}
		$project->SetObjectManager($manager);
	}

	/**
	 * Gets the config defined for this ProjectList
	 *
	 * @return mixed project config
	 */
	public function GetProjectListConfig()
	{
		return $this->projectConfig;
	}

	/**
	 * Gets the settings applied to this projectlist
	 *
	 * @return array
	 */
	public function GetProjectSettings()
	{
		return $this->projectSettings;
	}

	/**
	 * Reads the project's git config settings and applies them to the project
	 *
	 * @param GitPHP_Project $project project
	 */
	protected function ApplyGitConfig($project)
	{
		if (!$project)
			return;

		$config = null;
		try {
			$config = new GitPHP_GitConfig($project->GetPath() . '/config');
		} catch (Exception $e) {
			return;
		}

		if ($config->HasValue('gitphp.owner')) {
			$project->SetOwner($config->GetValue('gitphp.owner'));
		} else if ($config->HasValue('gitweb.owner')) {
			$project->SetOwner($config->GetValue('gitweb.owner'));
		}

		if ($config->HasValue('gitphp.description')) {
			$project->SetDescription($config->GetValue('gitphp.description'));
		} else if ($config->HasValue('gitweb.description')) {
			$project->SetDescription($config->GetValue('gitweb.description'));
		}

		if ($config->HasValue('gitphp.category')) {
			$project->SetCategory($config->GetValue('gitphp.category'));
		}

		if ($config->HasValue('gitphp.cloneurl')) {
			$project->SetCloneUrl($config->GetValue('gitphp.cloneurl'));
		}

		if ($config->HasValue('gitphp.pushurl')) {
			$project->SetPushUrl($config->GetValue('gitphp.pushurl'));
		}

		if ($config->HasValue('gitphp.bugurl')) {
			$project->SetBugUrl($config->GetValue('gitphp.bugurl'));
		}

		if ($config->HasValue('gitphp.bugpattern')) {
			$project->SetBugPattern($config->GetValue('gitphp.bugpattern'));
		}

		if ($config->HasValue('gitphp.website')) {
			$project->SetWebsite($config->GetValue('gitphp.website'));
		}

		if ($config->HasValue('gitphp.compat')) {
			$project->SetCompat($config->GetValue('gitphp.compat'));
		}

		if ($config->HasValue('core.abbrev')) {
			$project->SetAbbreviateLength($config->GetValue('core.abbrev'));
		}

		if ($config->HasValue('gitphp.allowedusers')) {
			$project->SetAllowedUsers($config->GetValue('gitphp.allowedusers', true));
		}

	}

	/**
	 * Applies global config settings to a project
	 *
	 * @param GitPHP_Project $project project
	 */
	protected function ApplyGlobalConfig($project)
	{
		if (!$project)
			return;

		if (!$this->config)
			return;

		if ($this->config->GetValue('cloneurl')) {
			$project->SetCloneUrl(GitPHP_Util::AddSlash($this->config->GetValue('cloneurl'), false) . $project->GetProject());
		}

		if ($this->config->GetValue('pushurl')) {
			$project->SetPushUrl(GitPHP_Util::AddSlash($this->config->GetValue('pushurl'), false) . $project->GetProject());
		}

		if ($this->config->GetValue('bugpattern')) {
			$project->SetBugPattern($this->config->GetValue('bugpattern'));
		}

		if ($this->config->GetValue('bugurl')) {
			$project->SetBugUrl($this->config->GetValue('bugurl'));
		}

		if ($this->config->HasKey('compat')) {
			$project->SetCompat($this->config->GetValue('compat'));
		}

		if ($this->config->HasKey('uniqueabbrev')) {
			$project->SetUniqueAbbreviation($this->config->GetValue('uniqueabbrev'));
		}

		if ($this->config->GetValue('abbreviateurl')) {
			$project->SetUniqueAbbreviation(true);
		}
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
	 * Filter projects by user access
	 *
	 * @param string $username username
	 */
	public function FilterByUser($username)
	{
		foreach ($this->projects as $path => $project) {
			if (!$project->UserCanAccess($username))
				unset($this->projects[$path]);
		}
	}

	/**
	 * Populates the internal list of projects
	 */
	abstract protected function PopulateProjects();

	/**
	 * Rewinds the iterator
	 *
	 * @return GitPHP_Project
	 */
	function rewind()
	{
		return reset($this->projects);
	}

	/**
	 * Returns the current element in the array
	 *
	 * @return GitPHP_Project
	 */
	function current()
	{
		return current($this->projects);
	}

	/**
	 * Returns the current key
	 *
	 * @return string
	 */
	function key()
	{
		return key($this->projects);
	}

	/**
	 * Advance the pointer
	 *
	 * @return GitPHP_Project
	 */
	function next()
	{
		return next($this->projects);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		return key($this->projects) !== null;
	}

	/**
	 * Sorts the project list
	 *
	 * @param string $sortBy sort method
	 */
	public function Sort($sortBy = GitPHP_ProjectListBase::ProjectSort)
	{
		switch ($sortBy) {
			case GitPHP_ProjectListBase::DescriptionSort:
				uasort($this->projects, array('GitPHP_Project', 'CompareDescription'));
				break;
			case GitPHP_ProjectListBase::OwnerSort:
				uasort($this->projects, array('GitPHP_Project', 'CompareOwner'));
				break;
			case GitPHP_ProjectListBase::AgeSort:
				uasort($this->projects, array('GitPHP_Project', 'CompareAge'));
				break;
			case GitPHP_ProjectListBase::ProjectSort:
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
	 * @param string $pattern filter pattern
	 * @return GitPHP_Project[] array of filtered projects
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
	 * Applies override settings for a project
	 *
	 * @param GitPHP_Project $project the project object
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
		if (!empty($projData['allowedusers'])) {
			$project->SetAllowedUsers($projData['allowedusers']);
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
					break;

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
	protected function Log($message, $messagedata = null)
	{
		if (empty($message))
			return;

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::LoggableChange, array($message, $messagedata));
		}
	}

}
