<?php
/**
 * Custom exception for signalling display of a message to user
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_MessageException extends Exception
{

	/**
	 * Whether this is an error or informational
	 *
	 * @var boolean
	 */
	public $Error;

	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	public $StatusCode;
	
	/**
	 * Constructor
	 *
	 * @param string $message message string
	 * @param boolean $error true if this is an error rather than informational
	 * @param integer $statusCode HTTP status code to return
	 * @param integer $code exception code
	 */
	public function __construct($message, $error = false, $statusCode = 200, $code = 0) {
		$this->Error = $error;
		$this->StatusCode = $statusCode;
		parent::__construct($message, $code);
	}
}
