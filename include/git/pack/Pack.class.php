<?php
/**
 * Extracts data from a pack
 *
 * Based on code from Glip by Patrik Fimml
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
class GitPHP_Pack
{

	/**
	 * Commit object type
	 */
	const OBJ_COMMIT = 1;

	/**
	 * Tree object type
	 */
	const OBJ_TREE = 2;

	/**
	 * Blob object type
	 */
	const OBJ_BLOB = 3;

	/**
	 * Tag object type
	 */
	const OBJ_TAG = 4;

	/**
	 * Offset delta object type
	 */
	const OBJ_OFS_DELTA = 6;

	/**
	 * Ref delta object type
	 */
	const OBJ_REF_DELTA = 7;

	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * The hash of the pack
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * The object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

	/**
	 * Pack data reader
	 *
	 * @var GitPHP_PackData
	 */
	protected $packData;

	/**
	 * Caches object offsets
	 *
	 * @var array
	 */
	protected $offsetCache = array();

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash pack hash
	 * @param GitPHP_GitObjectLoader object loader
	 */
	public function __construct($project, $hash, $objectLoader)
	{
		if (!$project)
			throw new Exception('Project is required');

		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			throw new GitPHP_InvalidHashException($hash);
		}

		if (!$objectLoader)
			throw new Exception('Object loader is required');

		$this->hash = $hash;
		$this->project = $project;
		$this->objectLoader = $objectLoader;

		if (!file_exists($project->GetPath() . '/objects/pack/pack-' . $hash . '.idx')) {
			throw new Exception('Pack index does not exist');
		}

		$this->packData = new GitPHP_PackData($project->GetPath() . '/objects/pack/pack-' . $hash . '.pack', $objectLoader);
	}

	/**
	 * Gets the project
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Gets the hash
	 *
	 * @return string object hash
	 */
	public function GetHash()
	{
		return $this->hash;
	}

	/**
	 * Checks if an object exists in the pack
	 *
	 * @param string $hash object hash
	 * @return boolean true if object is in pack
	 */
	public function ContainsObject($hash)
	{
		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		return $this->FindPackedObject($hash) !== false;
	}

	/**
	 * Searches for an object's offset in the index
	 *
	 * @return int offset
	 * @param string $hash hash
	 */
	private function FindPackedObject($hash)
	{
		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		$indexFile = $this->GetProject()->GetPath() . '/objects/pack/pack-' . $this->hash . '.idx';

		if (isset($this->offsetCache[$hash])) {
			return $this->offsetCache[$hash];
		}

		$offset = false;

		$index = fopen($indexFile, 'rb');
		flock($index, LOCK_SH);

		$magic = fread($index, 4);
		if ($magic == "\xFFtOc") {
			$version = GitPHP_Pack::fuint32($index);
			if ($version == 2) {
				$offset = $this->SearchIndexV2($index, $hash);
			}
		} else {
			$offset = $this->SearchIndexV1($index, $hash);
		}
		flock($index, LOCK_UN);
		fclose($index);
		$this->offsetCache[$hash] = $offset;
		return $offset;
	}

	/**
	 * Seraches a version 1 index for a hash
	 *
	 * @param resource $index file pointer to index
	 * @param string $hash hash to find
	 * @return int pack offset if found
	 */
	private function SearchIndexV1($index, $hash)
	{
		/*
		 * index v1 struture:
		 * fanout table - 256*4 bytes
		 * offset/sha table - 24*count bytes (4 byte offset + 20 byte sha for each index)
		 */

		$binaryHash = pack('H40', $hash);

		/*
		 * get the start/end indices to search
		 * from the fanout table
		 */
		list($low, $high) = $this->ReadFanout($index, $binaryHash, 0);

		if ($low == $high) {
			return false;
		}

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

			$this->offsetCache[$name] = $off;

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
	 * Seraches a version 2 index for a hash
	 *
	 * @param resource $index file pointer to index
	 * @param string $hash hash to find
	 * @return int pack offset if found
	 */
	private function SearchIndexV2($index, $hash)
	{
		/*
		 * index v2 structure:
		 * magic and version - 2*4 bytes
		 * fanout table - 256*4 bytes
		 * sha listing - 20*count bytes
		 * crc checksums - 4*count bytes
		 * offsets - 4*count bytes
		 */
		$binaryHash = pack('H40', $hash);

		/*
		 * get the start/end indices to search
		 * from the fanout table
		 */
		list($low, $high) = $this->ReadFanout($index, $binaryHash, 8);
		if ($low == $high) {
			return false;
		}

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
	 * Finds the start/end index a hash will be located between, acconding to the fanout table
	 *
	 * @param resource $index index file pointer
	 * @param string $binaryHash binary encoded hash to find
	 * @param int $offset offset in the index file where the fanout table is located
	 * @return array Range where object can be located
	 */
	private function ReadFanout($index, $binaryHash, $offset)
	{
		/*
		 * fanout table has 255 4-byte integers
		 * indexed by the first byte of the object name.
		 * the value at that index is the index at which objects
		 * starting with that byte can be found
		 * (first level fan-out)
		 */
		if ($binaryHash{0} == "\x00") {
			$low = 0;
			fseek($index, $offset);
			$high = GitPHP_Pack::fuint32($index);
		} else {
			fseek($index, $offset + (ord($binaryHash{0}) - 1) * 4);
			$low = GitPHP_Pack::fuint32($index);
			$high = GitPHP_Pack::fuint32($index);
		}
		return array($low, $high);
	}

	/**
	 * Extracts an object from the pack
	 *
	 * @param string $hash hash of object to extract
	 * @param int $type output parameter, returns the type of the object
	 * @return string|boolean object content, or false if not found
	 */
	public function GetObject($hash, &$type = 0)
	{
		if (!$this->packData->Valid()) {
			throw new Exception('Unsupported pack file format');
		}

		$offset = $this->FindPackedObject($hash);
		if ($offset === false) {
			return false;
		}

		return $this->packData->GetObject($offset, $type);
	}

	/**
	 * Find hashes in packfile matching a prefix
	 *
	 * @param string $prefix hash prefix
	 * @return array matching hashes
	 */
	public function FindHashes($prefix)
	{
		if (empty($prefix)) {
			return array();
		}

		if (strlen($prefix) >= 40) {
			return array($prefix);
		}

		$indexFile = $this->GetProject()->GetPath() . '/objects/pack/pack-' . $this->hash . '.idx';

		$matches = array();

		$index = fopen($indexFile, 'rb');
		flock($index, LOCK_SH);

		$magic = fread($index, 4);
		if ($magic == "\xFFtOc") {
			$version = GitPHP_Pack::fuint32($index);
			if ($version == 2) {
				$matches = $this->FindHashesV2($index, $prefix);
			}
		} else {
			$matches = $this->FindHashesV1($index, $prefix);
		}
		flock($index, LOCK_UN);
		fclose($index);
		return $matches;
	}

	/**
	 * Find hashes in v1 index matching a prefix
	 *
	 * @param resource $index file pointer to index
	 * @param string $prefix hash prefix
	 * @return array matching hashes
	 */
	private function FindHashesV1($index, $prefix)
	{
		$matches = array();

		$binaryPrefix = pack('H' . strlen($prefix), $prefix);
		list($low, $high) = $this->ReadFanout($index, $binaryPrefix, 0);

		$range = $high - $low;
		$prefixlen = strlen($prefix);

		fseek($index, 4*256 + 24*$low);
		for ($i = 0; $i < $range; $i++) {
			$off = GitPHP_Pack::fuint32($index);
			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$this->offsetCache[$name] = $off;

			$cmp = substr_compare($name, $prefix, 0, $prefixlen);
			if ($cmp === 0) {
				$matches[] = $name;
			} else if ($cmp > 0) {
				break;
			}
		}

		return $matches;
	}

	/**
	 * Find hashes in v2 index matching a prefix
	 *
	 * @param resource $index file pointer to index
	 * @param string $prefix hash prefix
	 * @return array matching hashes
	 */
	private function FindHashesV2($index, $prefix)
	{
		$matches = array();

		$binaryPrefix = pack('H' . strlen($prefix), $prefix);
		list($low, $high) = $this->ReadFanout($index, $binaryPrefix, 8);

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

	/**
	 * Unpacks a packed 32 bit integer
	 *
	 * @return int integer
	 * @param string $str binary data
	 */
	public static function uint32($str)
	{
		$a = unpack('Nx', substr($str, 0, 4));
		return $a['x'];
	}

	/**
	 * Reads and unpacks the next 32 bit integer
	 *
	 * @return int integer
	 * @param resource $handle file handle
	 */
	public static function fuint32($handle)
	{
		return GitPHP_Pack::uint32(fread($handle, 4));
	}
}
