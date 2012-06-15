<?php
/**
 * GitPHP GitObject
 *
 * Base class for all hash objects in a git repository
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Git Object class
 *
 * @abstract
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_GitObject
{
	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $projName;

	/**
	 * hash
	 *
	 * Stores the hash of the object internally
	 *
	 * @access protected
	 */
	protected $hash;

	/**
	 * abbreviatedHash
	 *
	 * Stores the abbreviated hash of the object internally
	 *
	 * @access protected
	 */
	protected $abbreviatedHash;

	/**
	 * abbreviatedHashLoaded
	 *
	 * Stores whether the abbreviated hash has been loaded
	 *
	 * @access protected
	 */
	protected $abbreviatedHashLoaded = false;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		//todo: remove this ref
		$this->project = $project;

		$this->projName = $project->GetProject();
		$this->SetHash($hash);
	}

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		return GitPHP_ProjectList::GetInstance()->GetProject($this->projName);
	}

	/**
	 * GetHash
	 *
	 * Gets the hash
	 *
	 * @access public
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string object hash
	 */
	public function GetHash($abbreviate = false)
	{
		if ($abbreviate) {
			if (!$this->abbreviatedHashLoaded) {
				$this->abbreviatedHash = $this->GetProject()->AbbreviateHash($this->hash);
				$this->abbreviatedHashLoaded = true;
			}

			if (!empty($this->abbreviatedHash)) {
				return $this->abbreviatedHash;
			}
		}

		return $this->hash;
	}

	/**
	 * SetHash
	 *
	 * Attempts to set the hash of this object
	 *
	 * @param string $hash the hash to set
	 * @throws Exception on invalid hash
	 * @access protected
	 */
	protected function SetHash($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
			throw new Exception(sprintf(__('Invalid hash %1$s'), $hash));
		}
		$this->hash = $hash;
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		return array('project', 'hash');
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key to use for this object
	 *
	 * @access public
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return 'project|' . $this->projName;
	}

}
