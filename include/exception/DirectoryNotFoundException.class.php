<?php
/**
 * Custom exception when a specified directory is not found
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_DirectoryNotFoundException extends GitPHP_MessageException
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
			$message = sprintf('Directory %1$s not found', $dir);
		parent::__construct($message, true, 404, $code);
	}
}
