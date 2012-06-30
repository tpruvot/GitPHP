<?php
/**
 * Memcache and memcached cache handler
 * 
 * Based on example memcache resource included with smarty
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_CacheResource_Memcache extends Smarty_CacheResource_KeyValueStore
{

	/**
	 * Memcache extension type constant
	 */
	const Memcache = 1;

	/**
	 * Memcached extension type constant
	 */
	const Memcached = 2;

	/**
	 * Memcache object
	 *
	 * @var Memcached|Memcache|null
	 */
	protected $memcacheObj = null;

	/**
	 * Memcache extension type
	 *
	 * @var int
	 */
	protected $memcacheType = 0;

	/**
	 * Memcache server array
	 *
	 * @var array[]
	 */
	protected $servers;

	/**
	 * Constructor
	 *
	 * @param array[] $servers server array
	 */
	public function __construct($servers)
	{
		if ((!$servers) || (!is_array($servers)) || (count($servers) < 1)) {
			throw new GitPHP_MessageException('No Memcache servers defined', true, 500);
		}

		if (class_exists('Memcached')) {

			$this->memcacheObj = new Memcached();
			$this->memcacheType = GitPHP_CacheResource_Memcache::Memcached;
			$this->memcacheObj->addServers($servers);


		} else if (class_exists('Memcache')) {

			$this->memcacheObj = new Memcache();
			$this->memcacheType = GitPHP_CacheResource_Memcache::Memcache;
			foreach ($servers as $server) {
				if (is_array($server)) {
					$host = $server[0];
					$port = 11211;
					if (isset($server[1]))
						$port = $server[1];
					$weight = 1;
					if (isset($server[2]))
						$weight = $server[2];
					$this->memcacheObj->addServer($host, $port, true, $weight);
				}
			}

		} else {
			throw new GitPHP_MissingMemcacheException();
		}

		$this->servers = $servers;
	}

	/**
	 * Read cached data
	 *
	 * @param array $keys array of keys to load
	 * @return array key/value cached data
	 */
	protected function read(array $keys)
	{
		$keymap = array();
		$hashedkeys = array();
		foreach ($keys as $key) {
			$newkey = sha1($key);
			$keymap[$newkey] = $key;
			$hashedkeys[] = $newkey;
		}

		$data = false;
		$cachedata = array();

		if ($this->memcacheType == GitPHP_CacheResource_Memcache::Memcache) {

			$cachedata = $this->memcacheObj->get($hashedkeys);

		} else if ($this->memcacheType == GitPHP_CacheResource_Memcache::Memcached) {

			$cachedata = $this->memcacheObj->getMulti($hashedkeys);
		}

		if ($cachedata) {
			foreach ($cachedata as $key => $value) {
				$origkey = $keymap[$key];
				if (!empty($origkey)) {
					$data[$origkey] = $value;
				}
			}
		}

		return $data;
	}

	/**
	 * Write data to cache
	 *
	 * @param array $keys array of key/value data to store
	 * @param int $expire expiration time
	 * @return boolean true on success
	 */
	protected function write(array $keys, $expire = null)
	{
		if ($this->memcacheType == GitPHP_CacheResource_Memcache::Memcache) {

			foreach ($keys as $key => $value) {
				$this->memcacheObj->set(sha1($key), $value, 0, $expire);
			}

			return true;

		} else if ($this->memcacheType == GitPHP_CacheResource_Memcache::Memcached) {

			$mapped = array();
			foreach ($keys as $key => $value) {
				$mapped[sha1($key)] = $value;
			}
			$this->memcacheObj->setMulti($mapped, $expire);
			
			return true;
		}

		return false;
	}

	/**
	 * Delete data from cache
	 *
	 * @param array $keys array of keys to delete
	 * @return boolean true on success
	 */
	protected function delete(array $keys)
	{
		foreach ($keys as $key) {
			$this->memcacheObj->delete(sha1($key));
		}
		return true;
	}

	/**
	 * Delete all data from cache
	 *
	 * @return boolean true on success
	 */
	protected function purge()
	{
		return $this->memcacheObj->flush();
	}

}
