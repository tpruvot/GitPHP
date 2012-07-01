<?php
/**
 * Interface for observer objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
interface GitPHP_Observer_Interface
{
	/**
	 * Change type for data that can be cached
	 *
	 * @var int
	 */
	const CacheableDataChange = 1;

	/**
	 * Change type for logger message
	 *
	 * @var int
	 */
	const LoggableChange = 2;

	/**
	 * Notify that observable object changed
	 *
	 * @param GitPHP_Observable_Interface $object object
	 * @param int $changeType type of change
	 * @param array $args argument array
	 */
	public function ObjectChanged($object, $changeType, $args = array());
}
