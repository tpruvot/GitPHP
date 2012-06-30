<?php
/**
 * Custom exception when search length is too short
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_SearchLengthException extends GitPHP_MessageException
{
	/**
	 * Minimum length
	 *
	 * @var int
	 */
	public $MinimumLength;

	/**
	 * Constructor
	 *
	 * @param int $minLength minimum search length
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($minLength, $message = '', $code = 0)
	{
		$this->MinimumLength = $minLength;
		if (empty($message)) {
			if ($minLength == 1)
				$message = 'You must enter search text of at least %1$d character';
			else
				$message = 'You must enter search text of at least %1$d characters';
			$message = sprintf($message, $minLength);
		}
		parent::__construct($message, true, 200, $code);
	}
}
