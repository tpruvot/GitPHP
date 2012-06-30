<?php
/** 
 * Custom exception when an invalid directory is configured
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidDirectoryConfigurationException extends GitPHP_MessageException
{
	/**
	 * Directory
	 *
	 * @var string
	 */
	public $Directory;

	/**
	 * Constructor
	 *
	 * @param string $dir directory
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($dir, $message = '', $code = 0)
	{
		$this->Directory = $dir;
		if (empty($message))
			$message = sprintf('%1$s is not a directory', $dir);
		parent::__construct($message, true, 500, $code);
	}
}
