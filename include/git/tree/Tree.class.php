<?php
/**
 * Represents a single tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tree
 */
class GitPHP_Tree extends GitPHP_FilesystemObject implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
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
	 * Observers
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Data load strategy
	 *
	 * @var GitPHP_TreeLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash tree hash
	 * @param GitPHP_TreeLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($project, $hash, $strategy)
	{
		parent::__construct($project, $hash);

		if (!$strategy)
			throw new Exception('Tree load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_TreeLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_TreeLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
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
				$obj = $this->GetProject()->GetObjectManager()->GetTree($data['hash']);
				if (isset($usedTrees[$data['hash']])) {
					$obj = clone $obj;
				} else {
					$usedTrees[$data['hash']] = 1;
				}
			} else if ($data['type'] == 'blob') {
				$obj = $this->GetProject()->GetObjectManager()->GetBlob($data['hash']);
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

		$this->contents = $this->strategy->Load($this);

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::CacheableDataChange);
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

		list($this->treePaths, $this->blobPaths) = $this->strategy->LoadHashPaths($this);
	}

	/**
	 * Add a new observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function AddObserver($observer)
	{
		if (!$observer)
			return;

		if (array_search($observer, $this->observers) !== false)
			return;

		$this->observers[] = $observer;
	}

	/**
	 * Remove an observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function RemoveObserver($observer)
	{
		if (!$observer)
			return;

		$key = array_search($observer, $this->observers);

		if ($key === false)
			return;

		unset($this->observers[$key]);
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
