<?php
/**
 * Base class representing a list of refs
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\RefList
 */
abstract class GitPHP_RefList implements Iterator
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The refs
	 *
	 * @var array
	 */
	protected $refs = array();

	/**
	 * The inverted refs
	 *
	 * @var array
	 */
	protected $invertedRefs = array();


	/**
	 * Whether data has been loaded
	 *
	 * @var boolean
	 */
	protected $dataLoaded = false;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 */
	public function __construct($project)
	{
		if (!$project)
			throw new Exception('Project is required');

		$this->project = $project;
	}

	/**
	 * Gets the project
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Loads data for this ref list
	 */
	protected abstract function LoadData();

	/**
	 * Checks if a ref exists
	 *
	 * @param string $ref ref name
	 * @return boolean true if exists
	 */
	public function Exists($ref)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		return isset($this->refs[$ref]);
	}

	/**
	 * Rewinds the iterator
	 *
	 * @return mixed
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->refs);
	}

	/**
	 * Returns the current ref
	 *
	 * @return mixed
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->refs);
	}

	/**
	 * Returns the current key
	 *
	 * @return mixed
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs);
	}

	/**
	 * Advance the pointer
	 *
	 * @return mixed
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->refs);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs) !== null;
	}

}
