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
	 * Template
	 *
	 * Cache template
	 */
	const Template = 'data.tpl';

	/**
	 * Stores the singleton instance of the object cache
	 * @deprecated
	 */
	protected static $objectCacheInstance;

	/**
	 * Return the singleton instance of the object cache
	 * @deprecated
	 *
	 * @return mixed instance of cache class
	 */
	public static function GetObjectCacheInstance()
	{
		if (!self::$objectCacheInstance) {
			$strategy = new GitPHP_Cache_File(GITPHP_CACHEDIR. 'objects', GitPHP_Config::GetInstance()->GetValue('objectcachecompress'));
			self::$objectCacheInstance = new GitPHP_Cache($strategy);
			if (GitPHP_Config::GetInstance()->GetValue('objectcache', false)) {
				self::$objectCacheInstance->SetEnabled(true);
				self::$objectCacheInstance->SetLifetime(GitPHP_Config::GetInstance()->GetValue('objectcachelifetime'));
			}
		}
		return self::$objectCacheInstance;
	}

	/**
	 * Smarty instance
	 * @deprecated
	 */
	protected $tpl = null;

	/**
	 * Stores whether the cache is enabled
	 * @deprecated
	 */
	protected $enabled = false;

	/**
	 * Cache strategy
	 *
	 * @var GitPHP_CacheStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Cache lifetime in seconds
	 *
	 * @var int
	 */
	protected $lifetime = 86400; // 24*60*60

	/**
	 * Constructor
	 *
	 * @param GitPHP_CacheStrategy_Interface $strategy cache strategy
	 */
	public function __construct(GitPHP_CacheStrategy_Interface $strategy)
	{
		if (!$strategy)
			throw new Exception('Cache strategy is required');

		$this->SetStrategy($strategy);

		if (!self::$objectCacheInstance) {
			self::$objectCacheInstance = $this;
		}
	}

	/**
	 * Set the cache strategy
	 *
	 * @param GitPHP_CacheStrategy_Interface $strategy cache strategy
	 */
	public function SetStrategy(GitPHP_CacheStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the cache lifetime
	 *
	 * @return int cache lifetime in seconds
	 */
	public function GetLifetime()
	{
		return $this->lifetime;
	}

	/**
	 * Sets the cache lifetime
	 */
	public function SetLifetime($lifetime)
	{
		if (!is_int($lifetime))
			return;

		$this->lifetime = $lifetime;
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
	 * Get an item from the cache
	 *
	 * @param string $key cache key
	 * @return the cached object, or false
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

		$servers = GitPHP_Config::GetInstance()->GetValue('memcache', null);
		if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
			$this->tpl->registerCacheResource('memcache', new GitPHP_CacheResource_Memcache($servers));
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
