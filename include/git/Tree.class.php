<?php
/**
 * GitPHP Tree
 *
 * Represents a single tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'FilesystemObject.class.php');
require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

/**
 * Tree class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tree extends GitPHP_FilesystemObject
{

	/**
	 * contents
	 *
	 * Tree contents
	 *
	 * @access protected
	 */
	protected $contents = array();

	/**
	 * contentsRead
	 *
	 * Stores whether contents were read
	 *
	 * @access protected
	 */
	protected $contentsRead = false;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash tree hash
	 * @return mixed tree object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetContents
	 *
	 * Gets the tree contents
	 *
	 * @access public
	 * @return array array of objects for contents
	 */
	public function GetContents()
	{
		if (!$this->contentsRead)
			$this->ReadContents();

		$contents = array();
		$usedTrees = array();
		$usedBlobs = array();

		for ($i = 0; $i < count($this->contents); ++$i) {
			$data = $this->contents[$i];
			$obj = null;

			if (!isset($data['hash']) || empty($data['hash']))
				continue;

			if ($data['type'] == 'tree') {
				$obj = $this->GetProject()->GetTree($data['hash']);
				if (isset($usedTrees[$data['hash']])) {
					$obj = clone $obj;
				} else {
					$usedTrees[$data['hash']] = 1;
				}
			} else if ($data['type'] == 'blob') {
				$obj = $this->GetProject()->GetBlob($data['hash']);
				if (isset($usedBlobs[$data['hash']])) {
					$obj = clone $obj;
				} else {
					$usedBlobs[$data['hash']] = 1;
				}

				if (isset($data['size']) && !empty($data['size'])) {
					$obj->SetSize($data['size']);
				}
			} else {
				continue;
			}

			if (isset($data['mode']) && !empty($data['mode']))
				$obj->SetMode($data['mode']);

			if (isset($data['path']) && !empty($data['path']))
				$obj->SetPath($data['path']);

			if ($this->commitHash)
				$obj->SetCommitHash($this->commitHash);

			$contents[] = $obj;
		}

		return $contents;
	}

	/**
	 * ReadContents
	 *
	 * Reads the tree contents
	 *
	 * @access protected
	 */
	protected function ReadContents()
	{
		$this->contentsRead = true;

		if ($this->GetProject()->GetCompat()) {
			$this->ReadContentsGit();
		} else {
			$this->ReadContentsRaw();
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * ReadContentsGit
	 *
	 * Reads the tree contents using the git executable
	 *
	 * @access private
	 */
	private function ReadContentsGit()
	{
		$exe = new GitPHP_GitExe($this->GetProject());

		$args = array();
		$args[] = '--full-name';
		if ($exe->CanShowSizeInTree())
			$args[] = '-l';
		$args[] = '-t';
		$args[] = $this->hash;
		
		$lines = explode("\n", $exe->Execute(GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})(\s+[0-9]+|\s+-)?\t(.+)$/", $line, $regs)) {
				switch($regs[2]) {
					case 'tree':
						$data = array();
						$data['type'] = 'tree';
						$data['hash'] = $regs[3];
						$data['mode'] = $regs[1];

						$path = $regs[5];
						if (!empty($this->path))
							$path = $this->path . '/' . $path;
						$data['path'] = $path;

						$this->contents[] = $data;
						break;
					case 'blob':
						$data = array();
						$data['type'] = 'blob';
						$data['hash'] = $regs[3];
						$data['mode'] = $regs[1];

						$path = $regs[5];
						if (!empty($this->path))
							$path = $this->path . '/' . $path;
						$data['path'] = $path;

						$size = trim($regs[4]);
						if (!empty($size))
							$data['size'] = $size;

						$this->contents[] = $data;
						break;
				}
			}
		}

	}

	/**
	 * ReadContentsRaw
	 *
	 * Reads the tree contents using the raw git object
	 *
	 * @access private
	 */
	private function ReadContentsRaw()
	{
		$treeData = $this->GetProject()->GetObject($this->hash);

		$start = 0;
		$len = strlen($treeData);
		while ($start < $len) {
			$pos = strpos($treeData, "\0", $start);

			list($mode, $path) = explode(' ', substr($treeData, $start, $pos-$start), 2);
			$mode = str_pad($mode, 6, '0', STR_PAD_LEFT);
			$hash = bin2hex(substr($treeData, $pos+1, 20));
			$start = $pos + 21;

			$octmode = octdec($mode);

			if ($octmode == 57344) {
				// submodules not currently supported
				continue;
			}

			if (!empty($this->path))
				$path = $this->path . '/' . $path;

			$data = array();
			$data['hash'] = $hash;
			if ($octmode & 0x4000) {
				// tree
				$data['type'] = 'tree';
			} else {
				// blob
				$data['type'] = 'blob';
			}

			$data['mode'] = $mode;
			$data['path'] = $path;

			$this->contents[] = $data;
		}
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('contents', 'contentsRead');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key to use for this object
	 *
	 * @access public
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Tree::CacheKey($this->project, $this->hash);
	}

	/**
	 * CacheKey
	 *
	 * Generates a tree cache key
	 *
	 * @access public
	 * @static
	 * @param string $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		return 'project|' . $proj . '|tree|' . $hash;
	}

}
