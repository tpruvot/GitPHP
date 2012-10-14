<?php
/**
 * Parses Git configuration files
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_GitConfig
{

	/**
	 * Default config value type (no conversion)
	 *
	 * @var int
	 */
	const TypeDefault = 1;

	/**
	 * Integer config value type
	 *
	 * @var int
	 */
	const TypeInteger = 2;

	/**
	 * Boolean config value type
	 *
	 * @var int
	 */
	const TypeBoolean = 3;
	
	/**
	 * The config file path
	 *
	 * @var string
	 */
	protected $configPath = null;

	/**
	 * Whether the config file has been loaded
	 *
	 * @var string
	 */
	protected $configRead = false;

	/**
	 * Config values
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * Instantiates object
	 *
	 * @param string $configPath config file path
	 */
	public function __construct($configPath)
	{
		if (!is_readable($configPath)) {
			throw new Exception('Git config file not readable');
		}

		$this->configPath = $configPath;
	}

	/**
	 * Gets a config value
	 *
	 * @param string $key config key
	 * @param boolean $multiValue true to support multivalue config variables
	 * @param int $forceType force interpretation as a certain type
	 * @return mixed config value
	 */
	public function GetValue($key, $multiValue = false, $forceType = null)
	{
		if (empty($key)) {
			return null;
		}

		if (!$this->configRead)
			$this->LoadConfig();

		if (!isset($this->config[$key])) {
			return null;
		}

		$values = array();

		$valueType = null;
		if ($forceType !== null) {
			$valueType = $forceType;
		} else {
			$valueType = GitPHP_GitConfig::GetType($key);
		}

		if ($valueType != GitPHP_GitConfig::TypeDefault) {
			// type conversion
			foreach ($this->config[$key] as $value) {
				switch ($valueType) {
					case GitPHP_GitConfig::TypeInteger:
						$value = intval($value);
						break;
					case GitPHP_GitConfig::TypeBoolean:
						$value = GitPHP_GitConfig::ToBool($value);
						break;
				}
				$values[] = $value;
			}
		} else {
			$values = $this->config[$key];
		}

		if (!$multiValue) {
			// single value
			return $values[0];
		} else {
			// multivalue
			return $values;
		}
	}

	/**
	 * Tests if a config value exists
	 *
	 * @param string $key config key
	 * @return boolean true if value exists
	 */
	public function HasValue($key)
	{
		if (empty($key)) {
			return false;
		}

		if (!$this->configRead)
			$this->LoadConfig();

		return isset($this->config[$key]);
	}

	/**
	 * Loads the config data
	 */
	private function LoadConfig()
	{
		$this->configRead = true;

		$data = explode("\n", file_get_contents($this->configPath));

		$currentSection = '';
		$currentSetting = '';
		foreach ($data as $line) {
			$trimmed = trim($line);
			if (empty($trimmed)) {
				continue;
			}

			if (preg_match('/^\[(.+)\]$/', $trimmed, $regs)) {
				// section

				$currentSection = '';
				$currentSetting = '';
				$trimmedSection = trim($regs[1]);
				if (preg_match('/^([0-9A-Za-z\.\-]+)( "(.+)")?$/', $trimmedSection, $subRegs)) {
					$currentSection = strtolower($subRegs[1]);
					if (!empty($subRegs[3])) {
						// subsection
						$currentSection .= '.' . $subRegs[3];
					}
				}
			} else if (!empty($currentSection)) {
				// variable

				$currentSetting .= $trimmed;
				
				if (substr($trimmed, -1) === '\\') {
					// continued on next line
					continue;
				}

				$key = '';
				$value = null;

				$eq = strpos($currentSetting, '=');
				if ($eq !== false) {
					// key value pair
					$key = GitPHP_GitConfig::Unescape(trim(substr($currentSetting, 0, $eq)));
					$value = GitPHP_GitConfig::Unescape(trim(substr($currentSetting, $eq+1)));
				} else {
					// no equals is assumed true
					$key = GitPHP_GitConfig::Unescape($currentSetting);
					$value = "true";
				}

				if (!empty($key)) {
					$fullSetting = $currentSection . '.' . strtolower($key);

					$this->config[$fullSetting][] = $value;
				}

				$currentSetting = '';
			}
		}
	}

	/**
	 * Gets the default type of a config value
	 *
	 * @param string $key config key
	 * @return int config value type
	 */
	protected static function GetType($key)
	{
		switch ($key) {
			case 'gitphp.compat':
				return GitPHP_GitConfig::TypeBoolean;
			case 'core.abbrev':
				return GitPHP_GitConfig::TypeInteger;
		}

		return GitPHP_GitConfig::TypeDefault;
	}

	/**
	 * Interpret git config boolean values
	 *
	 * @param mixed $value value to interpret
	 * @return boolean boolean interpretation
	 */
	protected static function ToBool($value)
	{
		// true/false
		if (strncasecmp($value, 'true', 4) === 0) {
			return true;
		}
		if (strncasecmp($value, 'false', 5) === 0) {
			return false;
		}

		// on/off
		if (strncasecmp($value, 'on', 2) === 0) {
			return true;
		}
		if (strncasecmp($value, 'off', 3) === 0) {
			return false;
		}

		// 1/0 is handled by normal type conversion
		return (bool)$value;
	}

	/**
	 * Parses escaped values and comments from git configs
	 *
	 * @param string $value value
	 * @return string unescaped value
	 */
	protected static function Unescape($value)
	{
		if (strlen($value) == 0) {
			return '';
		}

		if ((substr($value, 0, 1) === '"') && (substr($value, -1) === '"')) {
			// escaped with quotes
			$value = substr($value, 1, -1);
		} else {
			// not quoted, strip comments
			$value = preg_replace('/(#|;).*$/', '', $value);
		}

		// replace backslashed chars
		$value = str_replace(array('\\\\', '\\"'), array('\\', '"'), $value);

		return $value;
	}

}

