<?php
/**
 * Custom exception when memcache extensions are missing
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_MissingMemcacheException extends GitPHP_MessageException
{
	/**
	 * Constructor
	 *
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($message = '', $code = 0)
	{
		if (empty($message))
			$message = 'The Memcached or Memcache PHP extension is required for Memcache support';
		parent::__construct($message, true, 500, $code);
	}
}
