<?php
/**
 * Project list singleton instance and factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectList
{

	/**
	 * The singleton instance of the projectlist
	 *
	 * @var GitPHP_ProjectListBase
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton instance
	 *
	 * @return GitPHP_ProjectListBase instance of projectlist
	 */
	public static function GetInstance()
	{
		return self::$instance;
	}

	/**
	 * Releases the singleton instance
	 */
	public static function DestroyInstance()
	{
		self::$instance = null;
	}

	/**
	 * Instantiates the singleton instance
	 *
	 * @param string $file config file with git projects
	 * @param boolean $legacy true if this is the legacy project config
	 * @throws Exception if there was an error reading the file
	 */
	public static function Instantiate($file = null, $legacy = false)
	{
		if (self::$instance)
			return;
			
		$projectRoot = GitPHP_Config::GetInstance()->GetValue('projectroot');


		if (!empty($file) && is_file($file) && include($file)) {
			if (isset($git_projects)) {
				if (is_string($git_projects)) {
					if (function_exists('simplexml_load_file') && GitPHP_ProjectListScmManager::IsSCMManager($git_projects)) {
						self::$instance = new GitPHP_ProjectListScmManager($projectRoot, $git_projects);
					} else {
						self::$instance = new GitPHP_ProjectListFile($projectRoot, $git_projects);
					}
				} else if (is_array($git_projects)) {
					if ($legacy) {
						self::$instance = new GitPHP_ProjectListArrayLegacy($projectRoot, $git_projects);
					} else {
						self::$instance = new GitPHP_ProjectListArray($projectRoot, $git_projects);
					}
				}
			}
		}

		if (!self::$instance) {

			self::$instance = new GitPHP_ProjectListDirectory($projectRoot, GitPHP_Config::GetInstance()->GetValue('exportedonly', false));
		}

		if (isset($git_projects_settings) && !$legacy)
			self::$instance->SetSettings($git_projects_settings);
	}

}

