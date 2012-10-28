<?php
/**
 * Custom exception when a required function is disabled
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_DisabledFunctionException extends GitPHP_MessageException
{
	/**
	 * Function
	 *
	 * @var string
	 */
	public $Function;

	/**
	 * Constructor
	 *
	 * @param string $function function
	 * @param string $message message
	 * @param integer $code exception code
	 */
	public function __construct($function, $message = '', $code = 0)
	{
		$this->Function = $function;
		if (empty($message))
			$message = sprintf('Required function %1$s has been disabled', $function);
		parent::__construct($message, true, 500, $code);
	}
}
