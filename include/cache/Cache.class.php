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
	protected $lifetime = 86400;

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
	 *
	 * @param int $lifetime cache lifetime in seconds
	 */
	public function SetLifetime($lifetime)
	{
		if (!is_int($lifetime))
			return;

		$this->lifetime = $lifetime;
	}

	/**
	 * Get an item from the cache
	 *
	 * @param string $key cache key
	 * @return mixed the cached object, or false
	 */
	public function Get($key)
	{
		if (empty($key))
			return false;

		return $this->strategy->Get($key);
	}

	/**
	 * Set an item in the cache
	 *
	 * @param string $key cache key
	 * @param mixed $value value
	 * @param int $lifetime override the lifetime for this data
	 */
	public function Set($key, $value, $lifetime = null)
	{
		if (empty($key) || empty($value))
			return;

		if ($lifetime === null)
			$lifetime = $this->lifetime;

		$this->strategy->Set($key, $value, $lifetime);
	}

	/**
	 * Tests if a key is cached
	 *
	 * @param string $key cache key
	 * @return boolean true if cached, false otherwise
	 */
	public function Exists($key)
	{
		if (empty($key))
			return false;

		return $this->strategy->Exists($key);
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

		$this->strategy->Delete($key);
	}

	/**
	 * Clear the cache
	 */
	public function Clear()
	{
		$this->strategy->Clear();
	}

}
