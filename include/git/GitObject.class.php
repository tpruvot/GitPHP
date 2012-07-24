<?php
/**
 * Base class for all hash objects in a git repository
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_GitObject
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The hash of the object
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * The abbreviated hash of the object
	 *
	 * @var string
	 */
	protected $abbreviatedHash;

	/**
	 * Whether the abbreviated hash has been loaded
	 *
	 * @var boolean
	 */
	protected $abbreviatedHashLoaded = false;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash object hash
	 */
	public function __construct($project, $hash)
	{
		$this->project = $project;
		$this->SetHash($hash);
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
	 * Sets the project
	 *
	 * @param GitPHP_Project $project project
	 */
	public function SetProject($project)
	{
		$this->project = $project;
	}

	/**
	 * Gets the hash
	 *
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
	 * Attempts to set the hash of this object
	 *
	 * @param string $hash the hash to set
	 * @throws Exception on invalid hash
	 */
	protected function SetHash($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
			throw new GitPHP_InvalidHashException($hash);
		}
		$this->hash = $hash;
	}

	/**
	 * Called to prepare the object for serialization
	 *
	 * @return string[] list of properties to serialize
	 */
	public function __sleep()
	{
		return array('hash');
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return 'project|' . $this->project;
	}

}
