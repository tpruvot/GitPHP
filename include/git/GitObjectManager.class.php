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
	 * MemoryCache instance
	 *
	 */
	protected $memoryCache = null;

	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

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
	 * Set executable
	 *
	 * @param GitPHP_GitExe $exe executable
	 */
	public function SetExe($exe)
	{
		$this->exe = $exe;
	}

	/**
	 * Get memory cache instance
	 */
	public function GetMemoryCache()
	{
		return $this->memoryCache;
	}

	/**
	 * Set memory cache instance
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
		if (empty($hash))
			return null;

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash)) {
			$fullHash = $this->project->ExpandHash($hash);
			if ($fullHash == $hash)
				throw new GitPHP_InvalidHashException($hash);
			$hash = $fullHash;
		}

		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return null;

		$key = GitPHP_Commit::CacheKey($this->project->GetProject(), $hash);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$commit = $memoryCache->Get($key);

		if (!$commit) {

			$commit = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($commit) {
				$commit->SetProject($this->project);
			} else {
				$commit = new GitPHP_Commit($this->project, $hash);
			}
			$commit->AddObserver($this);

			$commit->SetCompat($this->project->GetCompat());

			$memoryCache->Set($key, $commit);

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
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$tagObj = $memoryCache->Get($key);

		if (!$tagObj) {
			$tagObj = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($tagObj) {
				$tagObj->SetProject($this->project);
			} else {
				$tagObj = new GitPHP_Tag($this->project, $tag, $hash);
			}
			$tagObj->AddObserver($this);

			$tagObj->SetCompat($this->project->GetCompat());

			$memoryCache->Set($key, $tagObj);
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
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$headObj = $memoryCache->Get($key);

		if (!$headObj) {
			$headObj = new GitPHP_Head($this->project, $head, $hash);

			$memoryCache->Set($key, $headObj);
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

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash) && !$this->compat) {
			$fullHash = $this->project->ExpandHash($hash);
			if ($fullHash == $hash)
				throw new GitPHP_InvalidHashException($hash);
			$hash = $fullHash;
		}

		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return null;

		$key = GitPHP_Blob::CacheKey($this->project->GetProject(), $hash);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$blob = $memoryCache->Get($key);

		if (!$blob) {
			$blob = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($blob) {
				$blob->SetProject($this->project);
			} else {
				$blob = new GitPHP_Blob($this->project, $hash);
			}
			$blob->AddObserver($this);

			$blob->SetCompat($this->project->GetCompat());

			$memoryCache->Set($key, $blob);
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

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash) && !$this->compat) {
			$fullHash = $this->project->ExpandHash($hash);
			if ($fullHash == $hash)
				throw new GitPHP_InvalidHashException($hash);
			$hash = $fullHash;
		}

		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return null;

		$key = GitPHP_Tree::CacheKey($this->project->GetProject(), $hash);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$tree = $memoryCache->Get($key);

		if (!$tree) {
			$tree = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($tree) {
				$tree->SetProject($this->project);
			} else {
				$tree = new GitPHP_Tree($this->project, $hash);
			}
			$tree->AddObserver($this);

			$tree->SetCompat($this->project->GetCompat());

			$memoryCache->Set($key, $tree);
		}

		return $tree;
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

		if (!(($object instanceof GitPHP_Observable_Interface) && ($object instanceof GitPHP_Cacheable_Interface)))
			return;

		GitPHP_Cache::GetObjectCacheInstance()->Set($object->GetCacheKey(), $object);
	}

}
