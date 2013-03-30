<?php
/**
 * Configfile reader class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Config
{
	
	/**
	 * Stores the singleton instance
	 */
	protected static $instance;

	/**
	 * Stores the config values
	 */
	protected $values = array();

	/**
	 * Returns the singleton instance
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Config();
		}
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
	 * Class constructor
	 */
	private function __construct()
	{
		$this->InitializeDefaults();
	}

	/**
	 * Loads a config file
	 */
	public function LoadConfig($configFile)
	{
		// backwards compatibility for people who have been
		// making use of these variables in their title
		global $gitphp_version, $gitphp_appstring;

		if (!is_readable($configFile)) {
			throw new GitPHP_MessageException('Could not load config file ' . $configFile, true, 500);
		}

		if (!include($configFile)) {
			throw new GitPHP_MessageException('Could not read config file ' . $configFile, true, 500);
		}

		if (isset($gitphp_conf) && is_array($gitphp_conf))
			$this->values = array_merge($this->values, $gitphp_conf);
	}

	/**
	 * ClearConfig
	 *
	 * Clears all config values
	 *
	 * @access public
	 */
	public function ClearConfig()
	{
		$this->values = array();
		$this->InitializeDefaults();
	}

	/**
	 * GetValue
	 *
	 * Gets a config value
	 *
	 * @access public
	 * @param $key config key to fetch
	 * @param $default default config value to return
	 * @return mixed config value
	 */
	public function GetValue($key, $default = null)
	{
		if ($this->HasKey($key)) {
			return $this->values[$key];
		}
		return $default;
	}

	/**
	 * SetValue
	 *
	 * Sets a config value
	 *
	 * @access public
	 * @param string $key config key to set
	 * @param mixed $value value to set
	 */
	public function SetValue($key, $value)
	{
		if (empty($key)) {
			return;
		}
		if (empty($value)) {
			unset($this->values[$key]);
			return;
		}
		$this->values[$key] = $value;
	}

	/**
	 * HasKey
	 *
	 * Tests if the config has specified this key
	 *
	 * @access public
	 * @param string $key config key to find
	 * @return boolean true if key exists
	 */
	public function HasKey($key)
	{
		if (empty($key)) {
			return false;
		}
		return isset($this->values[$key]);
	}

	/**
	 * Initializes default config values
	 */
	private function InitializeDefaults()
	{
		$this->values['objectmemory'] = 0;
		$this->values['objectcache'] = false;
		$this->values['objectcachelifetime'] = 86400;
		$this->values['cache'] = false;
		$this->values['debug'] = false;
		$this->values['benchmark'] = false;
		$this->values['stylesheet'] = 'gitphpskin.css';
		$this->values['javascript'] = true;
		$this->values['googlejs'] = false;
		$this->values['search'] = true;
		$this->values['filesearch'] = true;
		$this->values['cacheexpire'] = true;
		$this->values['largeskip'] = 200;
		$this->values['filemimetype'] = true;
		$this->values['geshi'] = true;
		$this->values['exportedonly'] = false;
		$this->values['compressformat'] = GITPHP_COMPRESS_ZIP;
		$this->values['locale'] = 'en_US';
		$this->values['graphs'] = false;
		$this->values['objectcachecompress'] = 500;

		// gitphp-repo additions
		$this->values['bareonly'] = true;
		$this->values['reposupport'] = false;
		$this->values['subfolder_levels'] = 1;
		$this->values['showremotes'] = false;
		$this->values['projectlist_show_owner'] = true;
		$this->values['projectlist_order'] = 'project';
	}

}
