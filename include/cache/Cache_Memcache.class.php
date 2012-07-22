<?php
/**
 * Cache strategy using Memcache extension
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_Cache_Memcache implements GitPHP_CacheStrategy_Interface
{

	/**
	 * Memcache object
	 *
	 * @var Memcached
	 */
	protected $memcache;

	/**
	 * Constructor
	 *
	 * @param array[] $servers server array
	 */
	public function __construct($servers)
	{
		if (!class_exists('Memcache'))
			throw new Exception('Memcache extension not found');

		if ((!$servers) || (!is_array($servers)) || (count($servers) < 1)) {
			throw new GitPHP_MessageException('No Memcache servers defined', true, 500);
		}

		$this->memcache = new Memcache();

		foreach ($servers as $server) {
			if (is_array($server)) {
				$host = $server[0];
				$port = 11211;
				if (isset($server[1]))
					$port = $server[1];
				$weight = 1;
				if (isset($server[2]))
					$weight = $server[2];
				$this->memcache->addServer($host, $port, true, $weight);
			}
		}
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

		return $this->memcache->get($key);
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

		$this->memcache->set($key, $value, false, time() + $lifetime);
	}

	/**
	 * Check if an item exists
	 *
	 * @param string $key cache key
	 * @return boolean true if exists
	 */
	public function Exists($key)
	{
		return ($this->Get($key) !== false);
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

		$this->memcache->delete($key);
	}

	/**
	 * Clear the cache
	 */
	public function Clear()
	{
		$this->memcache->flush();
	}

}
