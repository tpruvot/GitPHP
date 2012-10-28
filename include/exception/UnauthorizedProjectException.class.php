<?php
/**
 * Custom exception when a user tries to access a project they don't have access to
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_UnauthorizedProjectException extends GitPHP_MessageException
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
	 * @param string $message message
	 * @param int $code code
	 */
	public function __construct($project, $message = '', $code = 0)
	{
		$this->Project = $project;
		if (empty($message))
			$message = sprintf('You are not authorized to access project %1$s', $project);
		parent::__construct($message, true, 403, $code); 
	}
}
