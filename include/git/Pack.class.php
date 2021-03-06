<?php
/**
 * Extracts data from a pack
 * Based on code from Glip by Patrik Fimml
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Pack
{

	/**
	 * Object types constants
	 */
	const OBJ_COMMIT = 1;
	const OBJ_TREE = 2;
	const OBJ_BLOB = 3;
	const OBJ_TAG = 4;
	const OBJ_OFS_DELTA = 6;
	const OBJ_REF_DELTA = 7;

	/**
	 * Stores the project internally
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Stores the hash of the pack
	 */
	protected $hash;

	/**
	 * Caches object offsets
	 */
	protected $offsetCache = array();

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash pack hash
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		if (!(preg_match('/[0-9a-f]{40}/', $hash))) {
			throw new GitPHP_InvalidHashException($hash);
		}

		if (is_string($project))
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject($project);
		else
			$this->project = $project;

		$pack = $this->project->GetPath() . '/objects/pack/pack-' . $hash;
		if (is_readable($pack . '.pack')) {
			if (!file_exists($pack . '.idx'))
				throw new GitPHP_MessageException('Pack index does not exist', false);

			$this->hash = $hash;
		}
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
	 * Check if the Pack Data was loaded
	 */
	public function Valid()
	{
		return isset($this->hash);
	}

	/**
	 * Checks if an object exists in the pack
	 *
	 * @param string $hash object hash
	 * @return boolean true if object is in pack
	 */
	public function ContainsObject($hash)
	{
		if (!$this->Valid()) {
			return false;
		}

		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		return $this->FindPackedObject($hash) !== false;
	}

	/**
	 * Searches for an object's offset in the index
	 *
	 * @param string $hash hash
	 * @return int offset
	 */
	private function FindPackedObject($hash)
	{
		if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
			return false;
		}

		$indexFile = $this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.idx';

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
	 * Finds the start/end index a hash will be located between,
	 * acconding to the fanout table
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
	 * @return string object content, or false if not found
	 */
	public function GetObject($hash, &$type = 0)
	{
		if (!$this->Valid()) {
			return false;
		}

		$offset = $this->FindPackedObject($hash);
		if ($offset === false) {
			return false;
		}

		$pack = fopen($this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.pack', 'rb');
		flock($pack, LOCK_SH);

		$magic = fread($pack, 4);
		$version = GitPHP_Pack::fuint32($pack);
		if ($magic != 'PACK' || $version != 2) {
			flock($pack, LOCK_UN);
			fclose($pack);
			throw new Exception('Unsupported pack format');
		}

		list($type, $data) = $this->UnpackObject($pack, $offset);

		flock($pack, LOCK_UN);
		fclose($pack);
		return $data;
	}

	/**
	 * Extracts an object at an offset
	 *
	 * @param resource $pack pack file pointer
	 * @param int $offset object offset
	 * @return array object type and data
	 */
	private function UnpackObject($pack, $offset)
	{
		fseek($pack, $offset);

		/*
		 * object header:
		 * first byte is the type (high 3 bits) and low byte of size (lower 4 bits)
		 * subsequent bytes each have 7 next higher bits of the size (little endian)
		 * most significant bit is either 1 or 0 to indicate whether the next byte
		 * should be read as part of the size.  1 means continue reading the size,
		 * 0 means the data is starting
		 */
		$c = ord(fgetc($pack));
		$type = ($c >> 4) & 0x07;
		$size = $c & 0x0F;
		for ($i = 4; $c & 0x80; $i += 7) {
			$c = ord(fgetc($pack));
			$size |= (($c & 0x7f) << $i);
		}

		if ($type == GitPHP_Pack::OBJ_COMMIT || $type == GitPHP_Pack::OBJ_TREE || $type == GitPHP_Pack::OBJ_BLOB || $type == GitPHP_Pack::OBJ_TAG) {
			/*
			 * regular gzipped object data
			 */
			return array($type, gzuncompress(fread($pack, $size+512), $size));
		} else if ($type == GitPHP_Pack::OBJ_OFS_DELTA) {
			/*
			 * delta of an object at offset
			 */
			$buf = fread($pack, $size+512+20);

			/*
			 * read the base object offset
			 * each subsequent byte's 7 least significant bits
			 * are part of the offset in decreasing significance per byte
			 * (opposite of other places)
			 * most significant bit is a flag indicating whether to read the
			 * next byte as part of the offset
			 */
			$pos = 0;
			$off = -1;
			do {
				$off++;
				$c = ord($buf{$pos++});
				$off = ($off << 7) + ($c & 0x7f);
			} while ($c & 0x80);

			/*
			 * next read the compressed delta data
			 */
			$delta = gzuncompress(substr($buf, $pos), $size);
			unset($buf);

			$baseOffset = $offset - $off;
			if ($baseOffset > 0) {
				/*
				 * read base object at offset and apply delta to it
				 */
				list($type, $base) = $this->UnpackObject($pack, $baseOffset);
				$data = GitPHP_Pack::ApplyDelta($delta, $base);
				return array($type, $data);
			}
		} else if ($type == GitPHP_Pack::OBJ_REF_DELTA) {
			/*
			 * delta of object with hash
			 */

			/*
			 * first the base object's hash
			 * load that object
			 */
			$hash = fread($pack, 20);
			$hash = bin2hex($hash);

			$objectLoader = $this->project->GetObjectLoader();
			if (!is_object($objectLoader))
				throw new GitPHP_MessageException(sprintf('Unable to get object loader on project %1$s', $project->GetProject()), true);

			$base = $objectLoader->GetObject($hash, $type);

			/*
			 * then the gzipped delta data
			 */
			$delta = gzuncompress(fread($pack, $size + 512), $size);
			$data = GitPHP_Pack::ApplyDelta($delta, $base);
			return array($type, $data);
		}

		return false;
	}

	/**
	 * Applies a binary delta to a base object
	 *
	 * @param string $delta delta string
	 * @param string $base base object data
	 * @return string patched content
	 */
	private static function ApplyDelta($delta, $base)
	{
		/*
		 * algorithm from patch-delta.c
		 */
		$pos = 0;
		$baseSize = GitPHP_Pack::ParseVarInt($delta, $pos);
		$resultSize = GitPHP_Pack::ParseVarInt($delta, $pos);

		$data = '';
		$deltalen = strlen($delta);
		while ($pos < $deltalen) {
			$opcode = ord($delta{$pos++});
			if ($opcode & 0x80) {
				$off = 0;
				if ($opcode & 0x01) $off = ord($delta{$pos++});
				if ($opcode & 0x02) $off |= ord($delta{$pos++}) <<  8;
				if ($opcode & 0x04) $off |= ord($delta{$pos++}) << 16;
				if ($opcode & 0x08) $off |= ord($delta{$pos++}) << 24;
				$len = 0;
				if ($opcode & 0x10) $len = ord($delta{$pos++});
				if ($opcode & 0x20) $len |= ord($delta{$pos++}) <<  8;
				if ($opcode & 0x40) $len |= ord($delta{$pos++}) << 16;
				if ($len == 0) $len = 0x10000;
				$data .= substr($base, $off, $len);
			} else if ($opcode > 0) {
				$data .= substr($delta, $pos, $opcode);
				$pos += $opcode;
			}
		}
		return $data;
	}

	/**
	 * Find hashes in packfile matching a prefix
	 *
	 * @param string $prefix hash prefix
	 * @return array matching hashes
	 */
	public function FindHashes($prefix)
	{
		if (empty($prefix) || !$this->Valid()) {
			return array();
		}

		if (strlen($prefix) >= 40) {
			return array($prefix);
		}

		$indexFile = $this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.idx';

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
	 * Reads a git-style packed variable length integer
	 * sequence of bytes, where each byte's 7 less significant bits
	 * are pieces of the int in increasing significance for each byte (little endian)
	 * the most significant bit of each byte is a flag whether to continue
	 * reading bytes or not
	 *
	 * @param string $str packed data string
	 * @param int $pos position in string to read from
	 * @return int parsed integer
	 */
	private static function ParseVarInt($str, &$pos=0)
	{
		$ret = 0;
		$byte = 0x80;
		for ($shift = 0; $byte & 0x80; $shift += 7) {
			$byte = ord($str{$pos++});
			$ret |= (($byte & 0x7F) << $shift);
		}
		return $ret;
	}

	/**
	 * Unpacks a packed 32 bit integer
	 *
	 * @return int integer
	 * @param string $str binary data
	 */
	private static function uint32($str)
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
	private static function fuint32($handle)
	{
		return GitPHP_Pack::uint32(fread($handle, 4));
	}
}
