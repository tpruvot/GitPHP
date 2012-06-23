<?php
/**
 * Manages creating and caching git object classes
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_GitObjectManager implements GitPHP_Observer_Interface
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Cache instance
	 *
	 * @var GitPHP_Cache
	 */
	protected $cache = null;

	/**
	 * MemoryCache instance
	 *
	 * @var GitPHP_MemoryCache
	 */
	protected $memoryCache = null;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 */
	public function __construct($project)
	{
		if (!$project)
			throw new Exception('Project is required');

		$this->project = $project;
	}

	/**
	 * Get project
	 *
	 * @return GitPHP_Project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Gets the cache instance being used
	 *
	 * @return GitPHP_Cache|null cache instance
	 */
	public function GetCache()
	{
		return $this->cache;
	}

	/**
	 * Set the cache instance to use
	 *
	 * @param GitPHP_Cache|null $cache cache instance
	 */
	public function SetCache($cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Gets the memory cache instance being used
	 *
	 * @return GitPHP_MemoryCache|null memory cache instance
	 */
	public function GetMemoryCache()
	{
		return $this->memoryCache;
	}

	/**
	 * Sets the memory cache instance to use
	 *
	 * @param GitPHP_MemoryCache|null $memoryCache memory cache instance
	 */
	public function SetMemoryCache($memoryCache)
	{
		$this->memoryCache = $memoryCache;
	}

	/**
	 * Get a commit
	 *
	 * @param string $hash commit hash
	 * @return GitPHP_Commit|null commit objet
	 */
	public function GetCommit($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return null;


		$key = GitPHP_Commit::CacheKey($this->project->GetProject(), $hash);

		$commit = null;
		if ($this->memoryCache)
			$commit = $this->memoryCache->Get($key);

		if (!$commit) {

			if ($this->cache) {
				$commit = $this->cache->Get($key);
			}

			if ($commit) {
				$commit->SetProject($this->project);
			} else {
				$commit = new GitPHP_Commit($this->project, $hash);
			}
			$commit->AddObserver($this);

			$commit->SetCompat($this->project->GetCompat());

			if ($this->memoryCache)
				$this->memoryCache->Set($key, $commit);

		}

		return $commit;
	}

	/**
	 * Gets a single tag
	 *
	 * @param string $tag tag to find
	 * @param string $hash hash of tag, if known
	 * @return GitPHP_Tag tag object
	 */
	public function GetTag($tag, $hash = '')
	{
		if (empty($tag))
			return null;

		$key = GitPHP_Tag::CacheKey($this->project->GetProject(), $tag);

		$tagObj = null;
		if ($this->memoryCache)
			$tagObj = $this->memoryCache->Get($key);

		if (!$tagObj) {

			if ($this->cache) {
				$tagObj = $this->cache->Get($key);
			}

			if ($tagObj) {
				$tagObj->SetProject($this->project);
			} else {
				$tagObj = new GitPHP_Tag($this->project, $tag, $hash);
			}
			$tagObj->AddObserver($this);

			$tagObj->SetCompat($this->project->GetCompat());

			if ($this->memoryCache)
				$this->memoryCache->Set($key, $tagObj);
		}

		return $tagObj;
	}

	/**
	 * Gets a single head
	 *
	 * @param string $head head to find
	 * @param string $hash hash of head, if known
	 * @return GitPHP_Head head object
	 */
	public function GetHead($head, $hash = '')
	{
		if (empty($head))
			return null;

		$key = GitPHP_Head::CacheKey($this->project->GetProject(), $head);

		$headObj = null;
		if ($this->memoryCache)
			$headObj = $this->memoryCache->Get($key);

		if (!$headObj) {
			$headObj = new GitPHP_Head($this->project, $head, $hash);

			if ($this->memoryCache)
				$this->memoryCache->Set($key, $headObj);
		}

		return $headObj;
	}

	/**
	 * Gets a blob
	 *
	 * @param string $hash blob hash
	 * @return GitPHP_Blob blob object
	 */
	public function GetBlob($hash)
	{
		if (empty($hash))
			return null;

		$key = GitPHP_Blob::CacheKey($this->project->GetProject(), $hash);

		$blob = null;
		if ($this->memoryCache)
			$blob = $this->memoryCache->Get($key);

		if (!$blob) {

			if ($this->cache) {
				$blob = $this->cache->Get($key);
			}

			if ($blob) {
				$blob->SetProject($this->project);
			} else {
				$blob = new GitPHP_Blob($this->project, $hash);
			}
			$blob->AddObserver($this);

			$blob->SetCompat($this->project->GetCompat());

			if ($this->memoryCache)
				$this->memoryCache->Set($key, $blob);
		}

		return $blob;
	}

	/**
	 * Gets a tree
	 *
	 * @param string $hash tree hash
	 * @return GitPHP_Tree tree object
	 */
	public function GetTree($hash)
	{
		if (empty($hash))
			return null;

		$key = GitPHP_Tree::CacheKey($this->project->GetProject(), $hash);
		$tree = null;
		if ($this->memoryCache)
			$tree = $this->memoryCache->Get($key);

		if (!$tree) {

			if ($this->cache) {
				$tree = $this->cache->Get($key);
			}

			if ($tree) {
				$tree->SetProject($this->project);
			} else {
				$tree = new GitPHP_Tree($this->project, $hash);
			}
			$tree->AddObserver($this);

			$tree->SetCompat($this->project->GetCompat());

			if ($this->memoryCache)
				$this->memoryCache->Set($key, $tree);
		}

		return $tree;
	}

	/**
	 * Gets a file diff
	 *
	 * @param string $fromHash source hash, can also be a diff-tree info line
	 * @param string $toHash target hash, required if $fromHash is a hash
	 * @return GitPHP_FileDiff file diff object
	 */
	public function GetFileDiff($fromHash, $toHash = '')
	{
		$fileDiff = new GitPHP_FileDiff($this->project, $fromHash, $toHash);
		$fileDiff->SetCache($this->cache);
		return $fileDiff;
	}

	/**
	 * Notify that observable object changed
	 *
	 * @param GitPHP_Observable_Interface $object object
	 * @param int $changeType type of change
	 * @param array $args argument array
	 */
	public function ObjectChanged($object, $changeType, $args = array())
	{
		if (!$object)
			return;

		if ($changeType !== GitPHP_Observer_Interface::CacheableDataChange)
			return;

		if (!$this->cache)
			return;

		if (!(($object instanceof GitPHP_Observable_Interface) && ($object instanceof GitPHP_Cacheable_Interface)))
			return;

		$this->cache->Set($object->GetCacheKey(), $object);
	}

}
