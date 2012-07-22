<?php
/**
 * Interface for cache provider strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
interface GitPHP_CacheStrategy_Interface
{
	/**
	 * Gets an item from the cache
	 *
	 * @param string $key cache key
	 * @return mixed cached object or false if not found
	 */
	public function Get($key);

	/**
	 * Sets an item into the cache
	 *
	 * @param string $key cache key
	 * @param mixed $value object to cache
	 * @param int $lifetime cached object lifetime
	 */
	public function Set($key, $value, $lifetime);

	/**
	 * Check if an item exists
	 *
	 * @param string $key cache key
	 * @return boolean true if exists
	 */
	public function Exists($key);

	/**
	 * Delete an item from the cache
	 *
	 * @param string $key cache key
	 */
	public function Delete($key);

	/**
	 * Clear the cache
	 */
	public function Clear();
}
