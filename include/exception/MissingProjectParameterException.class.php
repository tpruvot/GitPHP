<?php
/**
 * Custom exception when project is not specified
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_MissingProjectParameterException extends GitPHP_MessageException
{
	/**
	 * Constructor
	 *
	 * @param string $message message
	 * @param int $code code
	 */
	public function __construct($message = '', $code = 0)
	{
		if (empty($message))
			$message = 'Project is required';
		parent::__construct($message, true, 200, $code);
	}
}
