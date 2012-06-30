<?php
/**
 * Custom exception when the git executable is invalid
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidGitExecutableException extends GitPHP_MessageException
{
	/**
	 * Executable
	 *
	 * @var string
	 */
	public $Executable;

	/**
	 * Constructor
	 *
	 * @param string $exe executable
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($exe, $message = '', $code = 0)
	{
		$this->Executable = $exe;
		if (empty($message))
			$message = sprintf('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.', $exe, 'gitbin');
		parent::__construct($message, true, 500, $code);
	}
}
