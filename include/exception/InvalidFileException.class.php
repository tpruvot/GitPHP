<?php
/** 
 * Custom exception when an invalid file is specified
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidFileException extends GitPHP_MessageException
{
	/**
	 * File
	 *
	 * @var string
	 */
	public $File;

	/**
	 * Constructor
	 *
	 * @param string $file file
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($file, $message = '', $code = 0)
	{
		$this->File = $file;
		if (empty($message))
			$message = sprintf('%1$s is not a file', $file);
		parent::__construct($message, true, 200, $code);
	}
}
