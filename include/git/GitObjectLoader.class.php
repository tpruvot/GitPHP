<?php
/**
 * GitPHP GitObjectLoader
 *
 * Handles loading data from raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * GitObjectLoader
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_GitObjectLoader
{
	/**
	 * project
	 *
	 * Stores the project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * packs
	 *
	 * Stores the list of packs
	 *
	 * @access protected
	 */
	protected $packs = array();

	/**
	 * packsRead
	 *
	 * Stores whether packs have been read
	 *
	 * @access protected
	 */
	protected $packsRead = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 */
	public function __construct($project)
	{
		if (!$project)
			throw new Exception('Project is required');

		$this->project = $project;
	}

	/**
	 * GetObject
	 *
	 * Gets the raw content of an object
	 *
	 * @access public
	 * @param string $hash object hash
	 * @return string object data
	 */
	public function GetObject($hash, &$type = 0)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
			return false;
		}

		// first check if it's unpacked
		$path = $this->project->GetPath() . '/objects/' . substr($hash, 0, 2) . '/' . substr($hash, 2);
		if (file_exists($path)) {
			list($header, $data) = explode("\0", gzuncompress(file_get_contents($path)), 2);
			sscanf($header, "%s %d", $typestr, $size);
			switch ($typestr) {
				case 'commit':
					$type = GitPHP_Pack::OBJ_COMMIT;
					break;
				case 'tree':
					$type = GitPHP_Pack::OBJ_TREE;
					break;
				case 'blob':
					$type = GitPHP_Pack::OBJ_BLOB;
					break;
				case 'tag':
					$type = GitPHP_Pack::OBJ_TAG;
					break;
			}
			return $data;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		// then try packs
		foreach ($this->packs as $pack) {
			$data = $pack->GetObject($hash, $type);
			if ($data !== false) {
				return $data;
			}
		}

		return false;
	}

	/**
	 * ReadPacks
	 *
	 * Read the list of packs in the repository
	 *
	 * @access private
	 */
	private function ReadPacks()
	{
		$dh = opendir($this->project->GetPath() . '/objects/pack');
		if ($dh !== false) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match('/^pack-([0-9A-Fa-f]{40})\.idx$/', $file, $regs)) {
					$this->packs[] = new GitPHP_Pack($this->project, $regs[1]);
				}
			}
		}
		$this->packsRead = true;
	}

	/**
	 * EnsureUniqueHash
	 *
	 * Ensures a hash prefix is unique
	 *
	 * @access public
	 * @param string $hash full hash
	 * @param string $prefix abbreviated hash prefix
	 */
	public function EnsureUniqueHash($hash, $prefix)
	{
		if (empty($hash) || empty($prefix))
			return null;

		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash)))
			return $hash;

		if (preg_match('/[0-9A-Fa-f]{40}/', $prefix))
			return $prefix;

		$hashMap = array();

		$matches = $this->FindHashObjects($prefix);
		foreach ($matches as $matchingHash) {
			$hashMap[$matchingHash] = 1;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($prefix);
			foreach ($matches as $matchingHash) {
				$hashMap[$matchingHash] = 1;
			}
		}

		if (count($hashMap) == 0) {
			return $hash;
		}

		if (count($hashMap) == 1) {
			return $prefix;
		}

		for ($len = $abbrevLen+1; $len < 40; $len++) {
			$prefix = substr($hash, 0, $len);

			foreach ($hashMap as $matchingHash => $val) {
				if (substr_compare($matchingHash, $prefix, 0, $len) !== 0) {
					unset($hashMap[$matchingHash]);
				}
			}

			if (count($hashMap) == 1) {
				return $prefix;
			}
		}

		return $hash;
	}

	/**
	 * ExpandHash
	 *
	 * Expands an abbreviated hash to the full hash
	 *
	 * @access public
	 * @param string $abbrevHash
	 * @return string hash
	 */
	public function ExpandHash($abbrevHash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{4,39}/', $abbrevHash))) {
			return $abbrevHash;
		}

		$matches = $this->FindHashObjects($abbrevHash);
		if (count($matches) > 0) {
			return $matches[0];
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($abbrevHash);
			if (count($matches) > 0) {
				return $matches[0];
			}
		}

		return $abbrevHash;
	}

	/**
	 * FindHashObjects
	 *
	 * Finds loose hash files matching a given prefix
	 *
	 * @access private
	 * @param string $prefix hash prefix
	 * @return array array of hash objects
	 */
	private function FindHashObjects($prefix)
	{
		$matches = array();
		if (empty($prefix)) {
			return $matches;
		}

		$subdir = substr($prefix, 0, 2);
		$fulldir = $this->project->GetPath() . '/objects/' . $subdir;
		if (!is_dir($fulldir)) {
			return $matches;
		}

		$prefixlen = strlen($prefix);
		$dh = opendir($fulldir);
		if ($dh !== false) {
			while (($file = readdir($dh)) !== false) {
				$fullhash = $subdir . $file;
				if (substr_compare($fullhash, $prefix, 0, $prefixlen) === 0) {
					$matches[] = $fullhash;
				}
			}
		}
		return $matches;
	}

}
