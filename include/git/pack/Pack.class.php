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
	 * The hash of the pack
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * Pack index reader
	 *
	 * @var GitPHP_PackIndex
	 */
	protected $packIndex;

	/**
	 * Pack data reader
	 *
	 * @var GitPHP_PackData
	 */
	protected $packData;

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

		$this->packIndex = new GitPHP_PackIndex($project->GetPath() . '/objects/pack/pack-' . $hash . '.idx');

		$this->packData = new GitPHP_PackData($project->GetPath() . '/objects/pack/pack-' . $hash . '.pack', $objectLoader);
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

		if (!$this->packIndex->Valid())
			throw new Exception('Invalid pack index');

		return $this->packIndex->ContainsObject($hash);
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
		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		if (!$this->packIndex->Valid()) {
			throw new Exception('Invalid pack index');
		}

		if (!$this->packData->Valid()) {
			throw new Exception('Unsupported pack file format');
		}

		$offset = $this->packIndex->FindPackedObject($hash);
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

		if (!$this->packIndex->Valid()) {
			throw new Exception('Invalid pack index');
		}

		return $this->packIndex->FindHashes($prefix);
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
