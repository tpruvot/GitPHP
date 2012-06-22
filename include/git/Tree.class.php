<?php
/**
 * Represents a single tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tree extends GitPHP_FilesystemObject
{

	/**
	 * Tree contents
	 *
	 * @var array
	 */
	protected $contents = array();

	/**
	 * Whether contents were read
	 *
	 * @var boolean
	 */
	protected $contentsRead = false;

	/**
	 * Tree hash to path mappings
	 *
	 * @var array
	 */
	protected $treePaths = array();

	/**
	 * Blob hash to path mappings
	 *
	 * @var array
	 */
	protected $blobPaths = array();

	/**
	 * Whether hash paths have been read
	 */
	protected $hashPathsRead = false;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash tree hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * Sets the object path (overrides base)
	 *
	 * @param string $path object path
	 */
	public function SetPath($path)
	{
		if ($this->path == $path)
			return;

		if ($this->hashPathsRead) {
			$this->treePaths = array();
			$this->blobPaths = array();
			$this->hashPathsRead = false;
		}

		$this->path = $path;
	}

	/**
	 * Gets the tree contents
	 *
	 * @return (GitPHP_Tree|GitPHP_Blob)[] array of objects for contents
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
	 * Reads the tree contents
	 */
	protected function ReadContents()
	{
		$this->contentsRead = true;

		if ($this->compat) {
			$this->ReadContentsGit();
		} else {
			$this->ReadContentsRaw();
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * Reads the tree contents using the git executable
	 */
	private function ReadContentsGit()
	{
		$args = array();
		$args[] = '--full-name';
		if (GitPHP_GitExe::GetInstance()->CanShowSizeInTree())
			$args[] = '-l';
		$args[] = '-t';
		$args[] = $this->hash;
		
		$lines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_LS_TREE, $args));

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
	 * Reads the tree contents using the raw git object
	 */
	private function ReadContentsRaw()
	{
		$treeData = $this->GetProject()->GetObjectLoader()->GetObject($this->hash);

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
	 * Gets tree paths mapped to hashes
	 *
	 * @return array
	 */
	public function GetTreePaths()
	{
		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		return $this->treePaths;
	}

	/**
	 * Gets blob paths mapped to hashes
	 *
	 * @return array
	 */
	public function GetBlobPaths()
	{
		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		return $this->blobPaths;
	}

	/**
	 * Given a filepath, get its hash
	 *
	 * @param string $path path
	 * @return string hash
	 */
	public function PathToHash($path)
	{
		if (empty($path))
			return '';

		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		if (isset($this->blobPaths[$path])) {
			return $this->blobPaths[$path];
		}

		if (isset($this->treePaths[$path])) {
			return $this->treePaths[$path];
		}

		return '';
	}

	/**
	 * Read hash to path mappings
	 */
	private function ReadHashPaths()
	{
		$this->hashPathsRead = true;

		$this->ReadHashPathsGit();
	}

	/**
	 * Reads hash to path mappings using git exe
	 */
	private function ReadHashPathsGit()
	{
		$args = array();
		$args[] = '--full-name';
		$args[] = '-r';
		$args[] = '-t';
		$args[] = $this->hash;

		$lines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/", $line, $regs)) {
				switch ($regs[2]) {
					case 'tree':
						$this->treePaths[trim($regs[4])] = $regs[3];
						break;
					case 'blob';
						$this->blobPaths[trim($regs[4])] = $regs[3];
						break;
				}
			}
		}
	}

	/**
	 * Called to prepare the object for serialization
	 *
	 * @return string[] list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('contents', 'contentsRead');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Tree::CacheKey($this->project->GetProject(), $this->hash);
	}

	/**
	 * Generates a tree cache key
	 *
	 * @param string $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		return 'project|' . $proj . '|tree|' . $hash;
	}

}
