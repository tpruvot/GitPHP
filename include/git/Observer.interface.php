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
	 * @const
	 */
	const CacheableDataChange = 1;

	/**
	 * Notify that observable object changed
	 *
	 * @param GitPHP_Observable_Interface $object object
	 * @param int $changeType type of change
	 */
	public function ObjectChanged($object, $changeType);
}
