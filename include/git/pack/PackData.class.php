<?php
/**
 * Pack data reader
 *
 * Based on code from Glip by Patrik Fimml
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Pack
 */
class GitPHP_PackData
{
	/**
	 * Path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

	/**
	 * Whether this pack is valid
	 *
	 * @var boolean
	 */
	protected $valid = false;

	/**
	 * Whether this pack has been validated
	 *
	 * @var boolean
	 */
	protected $validated = false;

	/**
	 * Constructor
	 *
	 * @param string $path pack data file path
	 * @param GitPHP_GitObjectLoader $objectLoader object loader
	 */
	public function __construct($path, $objectLoader)
	{
		if (!is_readable($path))
			throw new Exception('Pack data file ' . $path . ' could not be read');

		if (!$objectLoader)
			throw new Exception('Object loader is required');

		$this->path = $path;
		$this->objectLoader = $objectLoader;
	}

	/**
	 * Tests if a pack file is valid
	 *
	 * @return boolean true if valid
	 */
	public function Valid()
	{
		if (!$this->validated) {

			$this->validated = true;

			$this->valid = false;

			$pack = fopen($this->path, 'rb');
			flock($pack, LOCK_SH);

			$magic = fread($pack, 4);
			$version = GitPHP_Pack::fuint32($pack);
			if ($magic == 'PACK' && $version == 2)
				$this->valid = true;

			flock($pack, LOCK_UN);
			fclose($pack);

		}

		return $this->valid;
	}

	/**
	 * Extracts an object from the pack
	 *
	 * @param int $offset object offset
	 * @param int $type output parameter, returns the type of the object
	 * @return string|boolean object content or false if not found
	 */
	public function GetObject($offset, &$type = 0)
	{
		$pack = fopen($this->path, 'rb');
		flock($pack, LOCK_SH);

		list($type, $data) = $this->UnpackObject($pack, $offset);

		flock($pack, LOCK_UN);
		fclose($pack);
		return $data;
	}

	/**
	 * Unpacks an object at an offset
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
			$delta = gzuncompress(mb_orig_substr($buf, $pos), $size);

			$baseOffset = $offset - $off;
			if ($baseOffset > 0) {
				/*
				 * read base object at offset and apply delta to it
				 */
				list($type, $base) = $this->UnpackObject($pack, $baseOffset);
				$data = GitPHP_PackData::ApplyDelta($delta, $base);
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
			$base = $this->objectLoader->GetObject($hash, $type);

			/*
			 * then the gzipped delta data
			 */
			$delta = gzuncompress(fread($pack, $size + 512), $size);

			$data = GitPHP_PackData::ApplyDelta($delta, $base);

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
		GitPHP_PackData::ParseVarInt($delta, $pos);	// base size
		GitPHP_PackData::ParseVarInt($delta, $pos);	// result size

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
				$data .= mb_orig_substr($base, $off, $len);
			} else if ($opcode > 0) {
				$data .= mb_orig_substr($delta, $pos, $opcode);
				$pos += $opcode;
			}
		}
		return $data;
	}

	/**
	 * Read a git packed variable length int
	 *
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

}
