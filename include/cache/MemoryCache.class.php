<?php
/**
 * Cache to manage objects in process memory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_MemoryCache
{
	/**
	 * Stores the objects in this cache
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Whether the cache will automatically manage the number of items
	 *
	 * @var boolean
	 */
	protected $autoManaged = true;

	/**
	 * Stores the last project that stored into this cache
	 *
	 * @var string
	 */
	protected $lastProject;

	/**
	 * Size of cache
	 *
	 * @var int
	 */
	protected $size;

	/**
	 * Class constructor
	 *
	 * @param int $size size of cache
	 */
	public function __construct($size = 0)
	{
		$this->size = $size;
	}

	/**
	 * Gets the size of this cache
	 *
	 * @return int size
	 */
	public function GetSize()
	{
		return $this->size;
	}

	/**
	 * Sets the size of this cache
	 *
	 * @param int $size size
	 */
	public function SetSize($size)
	{
		if ($this->size != $size) {
			$oldSize = $this->size;
			$this->size = $size;
			if (($size > 0) && ($size < $oldSize)) {
				$this->Evict();
			}
		}
	}

	/**
	 * Gets whether this cache is auto managing its size
	 *
	 * @return bool true if automanaged
	 */
	public function GetAutoManaged()
	{
		return $this->autoManaged;
	}

	/**
	 * Sets whether this cache should auto manage its size
	 *
	 * @param bool $autoManaged true if cache should automanage
	 */
	public function SetAutoManaged($autoManaged)
	{
		if (!$this->autoManaged && $autoManaged && (count($this->objects) > 0)) {
			end($this->objects);
			$lastKey = key($this->objects);
			if ($lastKey) {
				$this->lastProject = $this->ExtractProject($lastKey);
			}
		}
		$this->autoManaged = $autoManaged;
	}

	/**
	 * Gets an object from the cache
	 *
	 * @param string $key cache key
	 * @return mixed object from cache if found
	 */
	public function Get($key)
	{
		if (empty($key))
			return null;

		if (!isset($this->objects[$key]))
			return null;

		$object = $this->objects[$key];

		/*
		 * unset and reset to move to end of associative array
		 * (indicate as most recently used)
		 */
		unset($this->objects[$key]);
		$this->objects[$key] = $object;

		return $object;
	}

	/**
	 * Sets an object into the cache
	 *
	 * @param string $key cache key
	 * @param mixed $object object to cache
	 */
	public function Set($key, $object)
	{
		if (empty($key))
			return;

		if ($this->autoManaged) {
			$project = $this->ExtractProject($key);
			if (!empty($project) && (empty($this->lastProject) || ($this->lastProject != $project))) {
				if (count($this->objects) > 0) {
					$this->Clear();
				}
				$this->lastProject = $project;
			}
		}

		if (isset($this->objects[$key])) {
			/*
			 * unset so resetting will move to end of associative array
		 	 * (indicate as most recently used)
			 */
			unset($this->objects[$key]);
		} else {
			$this->Evict();
		}

		$this->objects[$key] = $object;
	}

	/**
	 * Check if a key exists in the cache
	 *
	 * @param string $key key
	 * @return bool true if key exists
	 */
	public function Exists($key)
	{
		if (empty($key))
			return false;

		return isset($this->objects[$key]);
	}

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key key
	 */
	public function Delete($key)
	{
		if (!$this->Exists($key))
			return;

		unset($this->objects[$key]);
	}

	/**
	 * Gets the count of items in this cache
	 *
	 * @return int count
	 */
	public function GetCount()
	{
		return count($this->objects);
	}

	/**
	 * Clear the cache
	 */
	public function Clear()
	{
		$this->objects = array();
		$this->lastProject = '';
	}

	/**
	 * Evicts items from the cache down to the size limit
	 */
	private function Evict()
	{
		if ($this->size < 1) {
			return;
		}

		while (count($this->objects) >= $this->size) {
			array_shift($this->objects);
		}
	}

	/**
	 * Extracts the project from a key
	 *
	 * @param string $key cache key
	 */
	private function ExtractProject($key)
	{
		if (empty($key))
			return '';

		if (strncmp($key, 'project|', 8) != 0) {
			return '';
		}

		strtok($key, '|');

		$project = strtok('|');

		return $project;
	}

}
