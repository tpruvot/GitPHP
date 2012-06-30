<?php
/** 
 * Custom exception when an invalid git repository is specified
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Exception
 */
class GitPHP_InvalidGitRepositoryException extends GitPHP_MessageException
{
	/**
	 * Repository
	 *
	 * @var string
	 */
	public $Repository;

	/**
	 * Constructor
	 *
	 * @param string $repo repository
	 * @param string $message message
	 * @param int $code exception code
	 */
	public function __construct($repo, $message = '', $code = 0)
	{
		$this->Repository = $repo;
		if (empty($message))
			$message = sprintf('%1$s is not a git repository', $repo);
		parent::__construct($message, true, 200, $code);
	}
}
