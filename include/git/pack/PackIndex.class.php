<?php
/**
 * Pack index reader
 *
 * Based on code from Glip by Patrik Fimml
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
class GitPHP_PackIndex
{
	/**
	 * Path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Whether this pack index has been initialized
	 *
	 * @var boolean
	 */
	protected $initialized = false;

	/**
	 * Index reader strategy
	 *
	 * @var GitPHP_PackIndexStrategy_Interface
	 */
	protected $strategy = null;

	/**
	 * Constructor
	 *
	 * @param string $path pack index file path
	 */
	public function __construct($path)
	{
		if (!is_readable($path))
			throw new Exception('Pack index file ' . $path . ' could not be read');

		$this->path = $path;
	}

	/**
	 * Initializes index reader strategy
	 */
	private function Initialize()
	{
		$this->initialized = true;
		$this->strategy = null;

		$index = fopen($this->path, 'rb');
		flock($index, LOCK_SH);

		$magic = fread($index, 4);
		if ($magic == "\xFFtOc") {
			$version = GitPHP_Pack::fuint32($index);
			if ($version == 2) {
				$this->strategy = new GitPHP_PackIndexReaderV2();
			}
		} else {
			$this->strategy = new GitPHP_PackIndexReaderV1();
		}

		flock($index, LOCK_UN);
		fclose($index);
	}

	/**
	 * Checks if this index is valid
	 *
	 * @return boolean true if valid
	 */
	public function Valid()
	{
		if (!$this->initialized)
			$this->Initialize();

		return $this->strategy != null;
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

		if (!$this->Valid())
			return false;

		return $this->FindPackedObject($hash) !== false;
	}

	/**
	 * Searches for an object's offset in the index
	 *
	 * @return int offset
	 * @param string $hash hash
	 */
	public function FindPackedObject($hash)
	{
		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		if (!$this->Valid())
			return false;

		$offset = false;

		$index = fopen($this->path, 'rb');
		flock($index, LOCK_SH);

		$offset = $this->SearchIndex($index, $hash);

		flock($index, LOCK_UN);
		fclose($index);

		return $offset;
	}

	/**
	 * Search index for a hash
	 *
	 * @param resource $index index file pointer
	 * @param string $hash hash
	 * @return int|boolean pack data file offset or false if not found
	 */
	private function SearchIndex($index, $hash)
	{
		list($low, $high) = $this->ReadFanout($index, $hash, $this->strategy->FanoutAddress());
		if ($low == $high)
			return false;

		return $this->strategy->SearchIndex($index, $hash, $low, $high);
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

		if (!$this->Valid())
			return array();

		$index = fopen($this->path, 'rb');
		flock($index, LOCK_SH);

		list($low, $high) = $this->ReadFanout($index, $prefix, $this->strategy->FanoutAddress());

		$matches = $this->strategy->FindHashes($index, $prefix, $low, $high);

		flock($index, LOCK_UN);
		fclose($index);

		return $matches;
	}

	/**
	 * Finds the start/end index a hash will be located between, acconding to the fanout table
	 *
	 * @param resource $index index file pointer
	 * @param string $hash hash to find
	 * @param int $offset offset in the index file where the fanout table is located
	 * @return array Range where object can be located
	 */
	private function ReadFanout($index, $hash, $offset)
	{
		$binaryHash = pack('H' . strlen($hash), $hash);

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

}
