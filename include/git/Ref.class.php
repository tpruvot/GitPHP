<?php
/**
 * Base class for ref objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_Ref extends GitPHP_GitObject
{
	
	/**
	 * Stores the ref name
	 */
	protected $refName;

	/**
	 * Stores the ref directory
	 */
	protected $refDir;

	/**
	 * Instantiates ref
	 *
	 * @param mixed $project the project
	 * @param string $refDir the ref directory
	 * @param string $refName the ref name
	 * @param string $refHash the ref hash
	 * @throws Exception if not a valid ref
	 */
	public function __construct($project, $refDir, $refName, $refHash = '')
	{
		$this->project = $project->GetProject();
		$this->refDir = $refDir;
		$this->refName = $refName;
		if (!empty($refHash)) {
			$this->SetHash($refHash);
		}
	}

	/**
	 * Gets the hash for this ref (overrides base)
	 *
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string object hash
	 */
	public function GetHash($abbreviate = false)
	{
		if (empty($this->hash))
			$this->FindHash();

		return parent::GetHash($abbreviate);
	}

	/**
	 * Looks up the hash for the ref
	 *
	 * @throws Exception if hash is not found
	 */
	protected function FindHash()
	{
		$args = array();
		$args[] = '--hash';
		$args[] = '--verify';
		$args[] = $this->GetRefPath();
		$hash = trim(GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_SHOW_REF, $args));

		if (!empty($hash)) {
			$this->SetHash($hash);
		}
	}

	/**
	 * Gets the ref name
	 *
	 * @return string ref name
	 */
	public function GetName()
	{
		return $this->refName;
	}

	/**
	 * Gets the ref directory
	 *
	 * @return string ref directory
	 */
	public function GetDirectory()
	{
		return $this->refDir;
	}

	/**
	 * Gets the path to the ref within the project
	 *
	 * @return string ref path
	 */
	public function GetRefPath()
	{
		if (strstr($this->refName,'/refs/tags/')) {
			$this->refName = substr(strstr($this->refName,'/refs/tags/'), 11);
			$this->refDir = 'tags';
		} elseif (strstr($this->refName,'/refs/remotes/')) {
			$this->refDir = 'remotes';
			$this->refName = substr(strstr($this->refName,'/refs/remotes/'), 14);
		}
		return 'refs/' . $this->refDir . '/' . $this->refName;
	}

	/**
	 * Gets the path to the ref including the project path
	 *
	 * @return string full ref path
	 */
	public function GetFullPath()
	{
		return $this->GetProject()->GetPath() . '/' . $this->GetRefPath();
	}

	/**
	 * Called to prepare the object for serialization
	 *
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('refName', 'refDir');

		return array_merge($properties, parent::__sleep());
	}

}
