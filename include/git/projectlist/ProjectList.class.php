<?php
/**
 * Project list factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\ProjectList
 */
class GitPHP_ProjectList
{

	/**
	 * Stores the singleton instance of the projectlist
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton instance
	 *
	 * @return mixed instance of projectlist
	 * @throws Exception if projectlist has not been instantiated yet
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
	 * Instantiates the project list
	 *
	 * @param GitPHP_Config $config config provider
	 * @param string $file config file with git projects
	 * @param boolean $legacy true if this is the legacy project config
	 * @throws Exception if there was an error reading the file
	 */
	public static function Instantiate($config, $file = null, $legacy = false)
	{
		if (self::$instance)
			return self::$instance;

		$instance = null;

		$projectRoot = $config->GetValue('projectroot');


		if (!empty($file) && is_file($file) && include($file)) {
			if (isset($git_projects)) {
				if (is_string($git_projects)) {
					if (function_exists('simplexml_load_file') && GitPHP_ProjectListScmManager::IsSCMManager($git_projects)) {
						$instance = new GitPHP_ProjectListScmManager($projectRoot, $git_projects);
					} elseif (function_exists('simplexml_load_file') && GitPHP_ProjectListManifest::IsRepoManifest($git_projects)) {
						$instance = new GitPHP_ProjectListManifest($projectRoot, $git_projects);
					} else {
						$instance = new GitPHP_ProjectListFile($projectRoot, $git_projects);
					}
				} else if (is_array($git_projects)) {
					if ($legacy) {
						$instance = new GitPHP_ProjectListArrayLegacy($projectRoot, $git_projects);
					} else {
						$instance = new GitPHP_ProjectListArray($projectRoot, $git_projects);
					}
				}
			}
		}

		if (!$instance) {
			$instance = new GitPHP_ProjectListDirectory($projectRoot, $config->GetValue('exportedonly'));
		}

		$instance->SetConfig($config);

		if (isset($git_projects_settings) && !$legacy)
			$instance->SetProjectSettings($git_projects_settings);

		self::$instance = $instance;

		return self::$instance;
	}

}

