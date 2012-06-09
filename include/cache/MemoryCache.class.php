<?php
/**
 * GitPHP MemoryCache
 *
 * Cache to manage objects in process memory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */

/**
 * MemoryCache class
 *
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_MemoryCache
{
	/**
	 * instance
	 *
	 * Stores the singleton instance
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance;

	/**
	 * objects
	 *
	 * Stores the objects in this cache
	 *
	 * @access protected
	 */
	protected $objects = array();

	/**
	 * autoManaged
	 *
	 * Whether the cache will automatically manage the number of items
	 *
	 * @access protected
	 */
	protected $autoManaged = true;

	/**
	 * lastProject
	 *
	 * Stores the last project that stored into this cache
	 *
	 * @access protected
	 */
	protected $lastProject;

	/**
	 * size
	 *
	 * Size of cache
	 *
	 * @access protected
	 */
	protected $size;

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of config class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_MemoryCache();
		}
		return self::$instance;
	}

	/**
	 * DestroyInstance
	 *
	 * Releases the singleton instance
	 *
	 * @access public
	 * @static
	 */
	public static function DestroyInstance()
	{
		self::$instance = null;
	}

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access private
	 * @param int $size size of cache
	 */
	private function __construct($size = null)
	{
		if ($size !== null) {
			$this->size = $size;
		} else {
			$this->size = GitPHP_Config::GetInstance()->GetValue('objectmemory', 0);
		}
	}

	/**
	 * GetSize
	 *
	 * Gets the size of this cache
	 *
	 * @access public
	 * @return int size
	 */
	public function GetSize()
	{
		return $this->size;
	}

	/**
	 * SetSize
	 *
	 * Sets the size of this cache
	 *
	 * @access public
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
	 * GetAutoManaged
	 *
	 * Gets whether this cache is auto managing its size
	 *
	 * @access public
	 * @return bool true if automanaged
	 */
	public function GetAutoManaged()
	{
		return $this->autoManaged;
	}

	/**
	 * SetAutoManaged
	 *
	 * Sets whether this cache should auto manage its size
	 *
	 * @access public
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
	 * Get
	 *
	 * Gets an object from the cache
	 *
	 * @access public
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
	 * Set
	 *
	 * Sets an object into the cache
	 *
	 * @access public
	 * @param string $key cache key
	 * @param mixed $object object to cache
	 */
	public function Set($key, $object)
	{
		if (empty($key))
			return;

		if ($this->autoManaged) {
			$project = $this->ExtractProject($key);
			if (empty($this->lastProject) || ($this->lastProject != $project)) {
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
	 * GetCount
	 *
	 * Gets the count of items in this cache
	 *
	 * @access public
	 * @return int count
	 */
	public function GetCount()
	{
		return count($this->objects);
	}

	/**
	 * Clear
	 *
	 * Clear the cache
	 *
	 * @access public
	 */
	public function Clear()
	{
		$this->objects = array();
		$this->lastProject = '';
	}

	/**
	 * Evict
	 *
	 * Evicts items from the cache down to the size limit
	 *
	 * @access private
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
	 * KeyToProject
	 *
	 * Extracts the project from a key
	 *
	 * @access private
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
