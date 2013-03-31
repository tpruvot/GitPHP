<?php
/**
 * Custom exception when trying to perform a disabled search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_SearchDisabledException extends GitPHP_MessageException
{
	/**
	 * Whether this exception is for file searching
	 *
	 * @var boolean
	 */
	public $FileSearch;

	/**
	 * Constructor
	 *
	 * @param boolean $fileSearch true if filesearch
	 * @param string $message message
	 * @param int $code code
	 */
	public function __construct($fileSearch = false, $message = '', $code = 0)
	{
		$this->FileSearch = $fileSearch;
		if (empty($message)) {
			if ($fileSearch)
				$message = 'File search has been disabled';
			else
				$message = 'Search has been disabled';
		}
		parent::__construct($message, true, 200, $code);
	}
}
