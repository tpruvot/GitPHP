<?php
/**
 * Class to store arbitrary data objects in smarty cache
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_Cache
{
	/**
	 * Cache template
	 */
	const Template = 'data.tpl';

	/**
	 * Stores the singleton instance of the object cache
	 *
	 * @var GitPHP_Cache
	 */
	protected static $objectCacheInstance;

	/**
	 * Return the singleton instance of the object cache
	 *
	 * @return GitPHP_Cache instance of cache class
	 */
	public static function GetObjectCacheInstance()
	{
		if (!self::$objectCacheInstance) {
			self::$objectCacheInstance = new GitPHP_Cache();
			if (GitPHP_Config::GetInstance()->GetValue('objectcache', false)) {
				self::$objectCacheInstance->SetServers(GitPHP_Config::GetInstance()->GetValue('memcache', null));
				self::$objectCacheInstance->SetEnabled(true);
				self::$objectCacheInstance->SetLifetime(GitPHP_Config::GetInstance()->GetValue('objectcachelifetime', 86400));
			}
		}
		return self::$objectCacheInstance;
	}

	/**
	 * Smarty instance
	 *
	 * @var Smarty
	 */
	protected $tpl = null;

	/**
	 * Stores whether the cache is enabled
	 *
	 * @var boolean
	 */
	protected $enabled = false;

	/**
	 * Stores memcache servers
	 *
	 * @var array[]
	 */
	protected $servers = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Gets whether the cache is enabled
	 *
	 * @return boolean true if enabled
	 */
	public function GetEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Sets whether the cache is enabled
	 *
	 * @param boolean $enable true to enable, false to disable
	 */
	public function SetEnabled($enable)
	{
		if ($enable == $this->enabled)
			return;

		$this->enabled = $enable;

		if ($this->enabled)
			$this->CreateSmarty();
		else
			$this->DestroySmarty();
	}

	/**
	 * Gets the cache lifetime
	 *
	 * @return int cache lifetime in seconds
	 */
	public function GetLifetime()
	{
		if (!$this->enabled)
			return false;

		return $this->tpl->cache_lifetime;
	}

	/**
	 * Sets the cache lifetime
	 *
	 * @param int $lifetime cache lifetime in seconds
	 */
	public function SetLifetime($lifetime)
	{
		if (!$this->enabled)
			return;

		$this->tpl->cache_lifetime = $lifetime;
	}

	/**
	 * Gets memcache server array
	 *
	 * @return array[] memcache array
	 */
	public function GetServers()
	{
		return $this->servers;
	}

	/**
	 * Sets memcache server array
	 *
	 * @param array[] $servers server array
	 */
	public function SetServers($servers)
	{
		if (($this->servers === null) && ($servers === null))
			return;

		$this->servers = $servers;

		if ($this->enabled) {
			$this->DestroySmarty();
			$this->CreateSmarty();
		}
	}
	 

	/**
	 * Get an item from the cache
	 *
	 * @param string $key cache key
	 * @return mixed the cached object, or false
	 */
	public function Get($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		if (!$this->tpl->isCached(GitPHP_Cache::Template, $key))
			return false;

		$data = $this->tpl->fetch(GitPHP_Cache::Template, $key);

		return unserialize(trim($data));
	}

	/**
	 * Set an item in the cache
	 *
	 * @param string $key cache key
	 * @param mixed $value value
	 * @param int $lifetime override the lifetime for this data
	 */
	public function Set($key = null, $value = null, $lifetime = null)
	{
		if (empty($key) || empty($value))
			return;

		if (!$this->enabled)
			return;

		$oldLifetime = null;
		if ($lifetime !== null) {
			$oldLifetime = $this->tpl->cache_lifetime;
			$this->tpl->cache_lifetime = $lifetime;
		}

		$this->Delete($key);
		$this->tpl->clearAllAssign();
		$this->tpl->assign('data', serialize($value));

		// Force it into smarty's cache
		$tmp = $this->tpl->fetch(GitPHP_Cache::Template, $key);
		unset($tmp);

		if ($lifetime !== null) {
			$this->tpl->cache_lifetime = $oldLifetime;
		}
	}

	/**
	 * Tests if a key is cached
	 *
	 * @param string $key cache key
	 * @return boolean true if cached, false otherwise
	 */
	public function Exists($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		return $this->tpl->isCached(GitPHP_Cache::Template, $key);
	}

	/**
	 * Delete an item from the cache
	 *
	 * @param string $key cache key
	 */
	public function Delete($key = null)
	{
		if (empty($key))
			return;

		if (!$this->enabled)
			return;

		$this->tpl->clearCache(GitPHP_Cache::Template, $key);
	}

	/**
	 * Clear the cache
	 */
	public function Clear()
	{
		if (!$this->enabled)
			return;

		$this->tpl->clearAllCache();
	}

	/**
	 * Instantiates Smarty cache
	 */
	private function CreateSmarty()
	{
		if ($this->tpl)
			return;

		require_once(GITPHP_SMARTYDIR . 'Smarty.class.php');
		$this->tpl = new Smarty;
		$this->tpl->addPluginsDir(GITPHP_INCLUDEDIR . 'smartyplugins');

		$this->tpl->caching = Smarty::CACHING_LIFETIME_SAVED;

		if (isset($this->servers) && is_array($this->servers) && (count($this->servers) > 0)) {
			$this->tpl->registerCacheResource('memcache', new GitPHP_CacheResource_Memcache($this->servers));
			$this->tpl->caching_type = 'memcache';
		}

	}

	/**
	 * Destroys Smarty cache
	 */
	private function DestroySmarty()
	{
		if (!$this->tpl)
			return;

		$this->tpl = null;
	}

}
