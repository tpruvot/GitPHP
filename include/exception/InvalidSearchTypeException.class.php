<?php
/**
 * Custom exception when an invalid search type is given
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidSearchTypeException extends GitPHP_MessageException
{
	/**
	 * Constructor
	 *
	 * @param string $message string
	 * @param integer $code exception code
	 */
	public function __construct($message = '', $code = 0)
	{
		if (empty($message))
			$message = 'Invalid search type';
		parent::__construct($message, true, 200, $code);
	}
}
