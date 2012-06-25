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
	 * The ref name
	 *
	 * @var string
	 */
	protected $refName;

	/**
	 * The ref directory
	 *
	 * @var string
	 */
	protected $refDir;

	/**
	 * Instantiates ref
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $refDir the ref directory
	 * @param string $refName the ref name
	 * @param string $refHash the ref hash
	 */
	public function __construct($project, $refDir, $refName, $refHash = '')
	{
		$this->project = $project;
		$this->refDir = $refDir;
		$this->refName = $refName;
		if (!empty($refHash)) {
			$this->SetHash($refHash);
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
	 * @return string[] list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('refName', 'refDir');

		return array_merge($properties, parent::__sleep());
	}

}
