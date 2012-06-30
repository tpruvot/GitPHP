<?php
/**
 * Custom exception when an invalid hash is specified
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidHashException extends GitPHP_MessageException
{
	/**
	 * Hash
	 *
	 * @var string
	 */
	public $Hash;

	/** 
	 * Constructor
	 *
	 * @param string $hash hash
	 * @param string $message message
	 * @param integer $code exception code
	 */
	public function __construct($hash, $message = '', $code = 0)
	{
		$this->Hash = $hash;
		if (empty($message))
			$message = 'Invalid hash %1$s';
		parent::__construct($message, true, 200, $code);
	}
}
