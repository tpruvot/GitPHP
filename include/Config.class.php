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
	 * Stores the config values
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->InitializeDefaults();
	}

	/**
	 * Loads a config file
	 *
	 * @param string $configFile config file to load
	 * @throws Exception on failure
	 */
	public function LoadConfig($configFile)
	{
		// backwards compatibility for people who have been
		// making use of these variables in their title
		global $gitphp_version, $gitphp_appstring;

		if (!is_readable($configFile)) {
			throw new GitPHP_InvalidConfigFileException($configFile);
		}

		if (!include($configFile)) {
			throw new GitPHP_InvalidConfigFileException($configFile);
		}

		if (isset($gitphp_conf) && is_array($gitphp_conf))
			$this->values = array_merge($this->values, $gitphp_conf);
	}

	/**
	 * Clears all config values
	 */
	public function ClearConfig()
	{
		$this->values = array();
		$this->InitializeDefaults();
	}

	/**
	 * Gets a config value
	 *
	 * @param string $key config key to fetch
	 * @return mixed config value
	 */
	public function GetValue($key)
	{
		if ($this->HasKey($key)) {
			return $this->values[$key];
		}

		return null;
	}

	/**
	 * Sets a config value
	 *
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
	 * Tests if the config has specified this key
	 *
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
	}

}
