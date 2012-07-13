<?php
/**
 * Cache strategy using files
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_Cache_File implements GitPHP_CacheStrategy_Interface
{
	/**
	 * Cache file directory
	 *
	 * @var string
	 */
	protected $cacheDir;

	/**
	 * Constructor
	 *
	 * @param string $cacheDir cache dir
	 */
	public function __construct($cacheDir)
	{
		if (file_exists($cacheDir)) {
			if (!is_dir($cacheDir)) {
				throw new Exception($cacheDir . ' exists but is not a directory');
			} else if (!is_writable($cacheDir)) {
				throw new Exception($cacheDir . ' is not writable');
			}
		} else {
			if (!mkdir($cacheDir, 0777))
				throw new Exception($cacheDir . ' could not be created');
			chmod($cacheDir, 0777);
		}

		$this->cacheDir = GitPHP_Util::AddSlash($cacheDir, true);
	}

	/**
	 * Gets an item from the cache
	 *
	 * @param string $key cache key
	 * @return mixed cached object or false if not found
	 */
	public function Get($key)
	{
		if (empty($key))
			return false;

		$data = $this->Load($key);
		if ($data === false)
			return false;

		return unserialize($data);
	}

	/**
	 * Sets an item into the cache
	 *
	 * @param string $key cache key
	 * @param mixed $value object to cache
	 * @param int $lifetime cached object lifetime
	 */
	public function Set($key, $value, $lifetime)
	{
		if (empty($key) || empty($value))
			return;

		$expire = '';
		if ($lifetime >= 0)
			$expire = time() + $lifetime;
		file_put_contents($this->cacheDir . $this->KeyToFile($key), $expire . "\n" . serialize($value));
	}

	/**
	 * Check if an item exists
	 *
	 * @param string $key cache key
	 * @return boolean true if exists
	 */
	public function Exists($key)
	{
		return ($this->Load($key) !== false);
	}

	/**
	 * Delete an item from the cache
	 *
	 * @param string $key cache key
	 */
	public function Delete($key)
	{
		if (empty($key))
			return;

		$file = $this->cacheDir . $this->KeyToFile($key);

		if (file_exists($file))
			unlink($file);
	}

	/**
	 * Clear the cache
	 */
	public function Clear()
	{
		if ($dh = opendir($this->cacheDir)) {
			while (($file = readdir($dh)) !== false) {
				if (($file == '.') || ($file == '..'))
					continue;
				if (file_exists($this->cacheDir . $file))
					unlink($this->cacheDir . $file);
			}
			closedir($dh);
		}
	}

	/**
	 * Load a key's serialized data
	 *
	 * @param string $key cache key
	 */
	private function Load($key)
	{
		if (empty($key))
			return false;

		$file = $this->cacheDir . $this->KeyToFile($key);
		if (!is_readable($file))
			return false;

		$contents = file_get_contents($file);
		if (empty($contents)) {
			unlink($file);
			return false;
		}

		$expire = strtok($contents, "\n");
		if (!empty($expire) && ($expire < time())) {
			unlink($file);
			return false;
		}

		$data = substr($contents, strlen($expire) + 1);
		if (empty($data)) {
			unlink($file);
			return false;
		}

		return $data;
	}

	/**
	 * Converts a key to a filename
	 *
	 * @param string $key key
	 * @return string filename
	 */
	private function KeyToFile($key)
	{
		if (empty($key))
			return '';

		$key = preg_replace('/[^\w\|]+/', '_', $key);
		$key = preg_replace('/\|/', '^', $key);
		return $key . '.dat';
	}
}
