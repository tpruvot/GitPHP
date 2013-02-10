<?php
/**
 * Pack index v1 reader strategy
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
class GitPHP_PackIndexReaderV1 implements GitPHP_PackIndexStrategy_Interface
{
	/**
	 * Gets the fanout table address
	 *
	 * @return int fanout address
	 */
	public function FanoutAddress()
	{
		return 0;
	}

	/**
	 * Search the index for a pack offset
	 *
	 * @param resource $index file pointer to index
	 * @param string $hash hash to find
	 * @param int $low low bound offset
	 * @param int $high high bound offset
	 * @return int|boolean pack offset or false if not found
	 */
	public function SearchIndex($index, $hash, $low, $high)
	{
		/*
		 * index v1 struture:
		 * fanout table - 256*4 bytes
		 * offset/sha table - 24*count bytes (4 byte offset + 20 byte sha for each index)
		 */

		/*
		 * binary serach for the index of the hash in the sha/offset listing
		 * between cur and after from the fanout
		 */
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			fseek($index, 4*256 + 24*$mid);

			$off = GitPHP_Pack::fuint32($index);
			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$cmp = strcmp($hash, $name);
			
			if ($cmp < 0) {
				$high = $mid - 1;
			} else if ($cmp > 0) {
				$low = $mid + 1;
			} else {
				return $off;
			}
		}

		return false;
	}

	/**
	 * Find hashes in index matching a prefix
	 *
	 * @param resource $index file pointer to index
	 * @param string $prefix hash prefix
	 * @param int $low low bound offset
	 * @param int $high high bound offset
	 * @return string[] matching hashes
	 */
	public function FindHashes($index, $prefix, $low, $high)
	{
		$matches = array();

		$range = $high - $low;
		$prefixlen = strlen($prefix);

		fseek($index, 4*256 + 24*$low);
		for ($i = 0; $i < $range; $i++) {
			GitPHP_Pack::fuint32($index);	// offset
			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$cmp = substr_compare($name, $prefix, 0, $prefixlen);
			if ($cmp === 0) {
				$matches[] = $name;
			} else if ($cmp > 0) {
				break;
			}
		}

		return $matches;
	}
}
