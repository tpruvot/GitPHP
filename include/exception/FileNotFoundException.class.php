<?php
/**
 * Custom exception when a specified file is not found
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_FileNotFoundException extends GitPHP_MessageException
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
			$message = sprintf('File %1$s not found', $file);
		parent::__construct($message, true, 404, $code);
	}
}
