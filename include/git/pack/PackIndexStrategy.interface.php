<?php
/**
 * Interface for pack index readers
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
interface GitPHP_PackIndexStrategy_Interface
{
	/**
	 * Gets the fanout table address
	 *
	 * @return int fanout address
	 */
	public function FanoutAddress();

	/**
	 * Search the index for a pack offset
	 *
	 * @param resource $index file pointer to index
	 * @param string $hash hash to find
	 * @param int $low low bound offset
	 * @param int $high high bound offset
	 * @return int|boolean pack offset or false if not found
	 */
	public function SearchIndex($index, $hash, $low, $high);

	/**
	 * Find hashes in index matching a prefix
	 *
	 * @param resource $index file pointer to index
	 * @param string $prefix hash prefix
	 * @param int $low low bound offset
	 * @param int $high high bound offset
	 * @return string[] matching hashes
	 */
	public function FindHashes($index, $prefix, $low, $high);
}
