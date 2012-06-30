<?php
/**
 * Custom exception when an invalid project is specified
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidProjectParameterException extends GitPHP_MessageException
{
	/**
	 * Specified project
	 *
	 * @var string
	 */
	public $Project;

	/**
	 * Constructor
	 *
	 * @param string $project project
	 * @param int $code code
	 */
	public function __construct($project, $code = 0)
	{
		$this->Project = $project;
		$message = sprintf('Invalid project %1$s', $project);
		parent::__construct($message, true, 200, $code); 
	}
}
