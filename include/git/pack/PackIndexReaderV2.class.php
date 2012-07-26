<?php
/**
 * Pack index v2 reader strategy
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
class GitPHP_PackIndexReaderV2 implements GitPHP_PackIndexStrategy_Interface
{
	/**
	 * Gets the fanout table address
	 *
	 * @return int fanout address
	 */
	public function FanoutAddress()
	{
		return 8;
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
		 * index v2 structure:
		 * magic and version - 2*4 bytes
		 * fanout table - 256*4 bytes
		 * sha listing - 20*count bytes
		 * crc checksums - 4*count bytes
		 * offsets - 4*count bytes
		 */


		/*
		 * get the object count from fanout[255]
		 */
		fseek($index, 8 + 4*255);
		$objectCount = GitPHP_Pack::fuint32($index);

		/*
		 * binary search for the index of the hash in the sha listing
		 * between cur and after from the fanout
		 */
		$objIndex = false;
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			fseek($index, 8 + 4*256 + 20*$mid);

			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$cmp = strcmp($hash, $name);

			if ($cmp < 0) {
				$high = $mid - 1;
			} else if ($cmp > 0) {
				$low = $mid + 1;
			} else {
				$objIndex = $mid;
				break;
			}
		}
		if ($objIndex === false) {
			return false;
		}

		/*
		 * get the offset from the same index in the offset table
		 */
		fseek($index, 8 + 4*256 + 24*$objectCount + 4*$objIndex);
		$offset = GitPHP_Pack::fuint32($index);
		if ($offset & 0x80000000) {
			throw new Exception('64-bit offsets not implemented');
		}
		return $offset;
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

		$prefixlen = strlen($prefix);

		fseek($index, 8 + 4*256 + 20*$low);
		for ($i = $low; $i < $high; $i++) {
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
