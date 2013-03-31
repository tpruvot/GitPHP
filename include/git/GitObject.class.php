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
	 * Stores the project internally
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Stores the project name for compat.
	 */
	protected $projectName;

	/**
	 * Stores the hash of the object internally
	 */
	protected $hash;

	/**
	 * Stores the abbreviated hash of the object internally
	 */
	protected $abbreviatedHash;

	/**
	 * Stores whether the abbreviated hash has been loaded
	 */
	protected $abbreviatedHashLoaded = false;

	/**
	 * Instantiates object
	 *
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		if (is_object($project)) {
			$this->projectName = $project->GetProject();
			$this->project = $project;
		} elseif (is_string($project)) {
			$this->projectName = $project;
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject($this->projectName);
		}
		$this->SetHash($hash);
	}

	/**
	 * Gets the project
	 *
	 * @return mixed project
	 */
	public function GetProject()
	{
		if (is_string($this->project)) {
			// hmm....
			$this->projectName = $this->project;
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject($this->projectName);
		}
		return $this->project;
	}

	/**
	 * Sets the project
	 *
	 * @param GitPHP_Project $project project
	 */
	public function SetProject($project)
	{
		if (is_object($project)) {
			$this->project = $project;
			$this->projectName = $project->GetProject();
		} elseif (is_string($project)) {
			$this->projectName = $project;
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject($this->projectName);
		}
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
		return 'project|' . $this->projectName;
	}

}
