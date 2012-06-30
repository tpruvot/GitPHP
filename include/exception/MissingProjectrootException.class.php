<?php
/**
 * Custom exception when projectroot is missing
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_MissingProjectrootException extends GitPHP_MessageException
{
	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code exception code
	 */
	public function __construct($message = '', $code = 0)
	{
		if (empty($message))
			$message = 'A projectroot must be set in the config';
		parent::__construct($message, true, 500, $code);
	}
}
