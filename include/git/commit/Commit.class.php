<?php
/**
 * Represents a single commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\Commit
 */
class GitPHP_Commit extends GitPHP_GitObject implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
{

	/**
	 * Whether data for this commit has been read
	 *
	 * @var boolean
	 */
	protected $dataRead = false;

	/**
	 * Array of parent commits
	 *
	 * @var string[]
	 */
	protected $parents = array();

	/**
	 * Tree hash for this commit
	 *
	 * @var string
	 */
	protected $tree;

	/**
	 * Author for this commit
	 *
	 * @var string
	 */
	protected $author;

	/**
	 * Author's epoch
	 *
	 * @var string
	 */
	protected $authorEpoch;

	/**
	 * Author's timezone
	 *
	 * @var string
	 */
	protected $authorTimezone;

	/**
	 * Committer for this commit
	 *
	 * @var string
	 */
	protected $committer;

	/**
	 * Committer's epoch
	 *
	 * @var string
	 */
	protected $committerEpoch;

	/**
	 * Committer's timezone
	 *
	 * @var string
	 */
	protected $committerTimezone;

	/**
	 * The commit title
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * The commit comment
	 *
	 * @var string
	 */
	protected $comment = array();

	/**
	 * Whether tree filenames have been read
	 *
	 * @var boolean
	 */
	protected $readTree = false;

	/**
	 * The tag containing the changes in this commit
	 *
	 * @var string
	 */
	protected $containingTag = null;

	/**
	 * Whether the containing tag has been looked up
	 *
	 * @var boolean
	 */
	protected $containingTagRead = false;

	/**
	 * Observers
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Data load strategy
	 *
	 * @var GitPHP_CommitLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash object hash
	 * @param GitPHP_CommitLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($project, $hash, GitPHP_CommitLoadStrategy_Interface $strategy)
	{
		parent::__construct($project, $hash);

		if (!$strategy)
			throw new Exception('Commit load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_CommitLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_CommitLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the hash for this commit (overrides base)
	 *
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string object hash
	 */
	public function GetHash($abbreviate = false)
	{
		if ($abbreviate && $this->strategy->LoadsAbbreviatedHash()) {
			if (!$this->dataRead)
				$this->ReadData();
		}

		return parent::GetHash($abbreviate);
	}

	/**
	 * Gets the main parent of this commit
	 *
	 * @return GitPHP_Commit|null commit object for parent
	 */
	public function GetParent()
	{
		$hash = $this->GetParentHash();
		if ($hash) {
			return $this->GetProject()->GetCommit($hash);
		}

		return null;
	}

	/**
	 * Gets the hash of the main parent of this commit
	 *
	 * @return string commit hash for parent
	 */
	public function GetParentHash()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (isset($this->parents[0]))
			return $this->parents[0];

		return null;
	}

	/**
	 * Gets an array of parent objects for this commit
	 *
	 * @return GitPHP_Commit[] array of commit objects
	 */
	public function GetParents()
	{
		$parenthashes = $this->GetParentHashes();

		$parents = array();
		foreach ($parenthashes as $parent) {
			$parents[] = $this->GetProject()->GetCommit($parent);
		}

		return $parents;
	}

	/**
	 * Gets an array of parent hashes for this commit
	 *
	 * @return string[] array of hashes
	 */
	public function GetParentHashes()
	{
		if (!$this->dataRead)
			$this->ReadData();

		$parents = $this->parents;
		return $parents;
	}

	/**
	 * Gets the tree for this commit
	 *
	 * @return GitPHP_Tree tree object
	 */
	public function GetTree()
	{
		$treehash = $this->GetTreeHash();

		if (empty($treehash))
			return null;

		$tree = $this->GetProject()->GetObjectManager()->GetTree($treehash);
		if ($tree) {
			$tree->SetCommitHash($this->hash);
			$tree->SetPath(null);
		}

		return $tree;
	}

	/**
	 * Gets the tree hash for this commit
	 *
	 * @return string tree hash
	 */
	public function GetTreeHash()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->tree;
	}

	/**
	 * Gets the author for this commit
	 *
	 * @return string author
	 */
	public function GetAuthor()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->author;
	}

	/**
	 * Gets the author's name only
	 *
	 * @return string author name
	 */
	public function GetAuthorName()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return preg_replace('/ <.*/', '', $this->author);
	}

	/**
	 * Gets the author's email only
	 *
	 * @return string author email
	 */
	public function GetAuthorEmail()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (preg_match('/ <(.*)>$/', $this->author, $regs)) {
			return $regs[1];
		}
	}

	/**
	 * Gets the author's epoch
	 *
	 * @return string author epoch
	 */
	public function GetAuthorEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->authorEpoch;
	}

	/**
	 * Gets the author's local epoch
	 *
	 * @return string author local epoch
	 */
	public function GetAuthorLocalEpoch()
	{
		$epoch = $this->GetAuthorEpoch();
		$tz = $this->GetAuthorTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * Gets the author's timezone
	 *
	 * @param boolean $separator true to return with separator
	 * @return string author timezone
	 */
	public function GetAuthorTimezone($separator = false)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($separator && preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $this->authorTimezone, $regs)) {
			return $regs[1] . ':' . $regs[2];
		}

		return $this->authorTimezone;
	}

	/**
	 * Gets the author for this commit
	 *
	 * @return string author
	 */
	public function GetCommitter()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->committer;
	}

	/**
	 * Gets the author's name only
	 *
	 * @return string author name
	 */
	public function GetCommitterName()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return preg_replace('/ <.*/', '', $this->committer);
	}

	/**
	 * Gets the committer's email only
	 *
	 * @return string committer email
	 */
	public function GetCommitterEmail()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (preg_match('/ <(.*)>$/', $this->committer, $regs)) {
			return $regs[1];
		}
	}

	/**
	 * Gets the committer's epoch
	 *
	 * @return string committer epoch
	 */
	public function GetCommitterEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->committerEpoch;
	}

	/**
	 * Gets the committer's local epoch
	 *
	 * @return string committer local epoch
	 */
	public function GetCommitterLocalEpoch()
	{
		$epoch = $this->GetCommitterEpoch();
		$tz = $this->GetCommitterTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * Gets the author's timezone
	 *
	 * @param boolean $separator true to return with separator
	 * @return string author timezone
	 */
	public function GetCommitterTimezone($separator = false)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($separator && preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $this->committerTimezone, $regs)) {
			return $regs[1] . ':' . $regs[2];
		}

		return $this->committerTimezone;
	}

	/**
	 * Gets the commit title
	 *
	 * @param integer $trim length to trim to (0 for no trim)
	 * @return string title
	 */
	public function GetTitle($trim = 0)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($trim > 0) {
			if (function_exists('mb_strimwidth')) {
				return mb_strimwidth($this->title, 0, $trim, '…');
			} else if (strlen($this->title) > $trim) {
				return substr($this->title, 0, $trim) . '…';
			}
		}

		return $this->title;
	}

	/**
	 * Gets the lines of comment
	 *
	 * @return string[] lines of comment
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
	}

	/**
	 * Gets the lines of the comment matching the given pattern
	 *
	 * @param string $pattern pattern to find
	 * @return string[] matching lines of comment
	 */
	public function SearchComment($pattern)
	{
		if (empty($pattern))
			return $this->GetComment();

		if (!$this->dataRead)
			$this->ReadData();

		return preg_grep('/' . $pattern . '/i', $this->comment);
	}

	/**
	 * Gets the age of the commit
	 *
	 * @return string age
	 */
	public function GetAge()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (!empty($this->committerEpoch))
			return time() - $this->committerEpoch;

		return '';
	}

	/**
	 * Returns whether this is a merge commit
	 *
	 * @return boolean true if merge commit
	 */
	public function IsMergeCommit()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->parents) > 1;
	}

	/**
	 * Read the data for the commit
	 */
	protected function ReadData()
	{
		$this->dataRead = true;

		list(
			$abbreviatedHash,
			$this->tree,
			$this->parents,
			$this->author,
			$this->authorEpoch,
			$this->authorTimezone,
			$this->committer,
			$this->committerEpoch,
			$this->committerTimezone,
			$this->title,
			$this->comment
		) = $this->strategy->Load($this);

		if (!empty($abbreviatedHash)) {
			$this->abbreviatedHash = $abbreviatedHash;
			$this->abbreviatedHashLoaded = true;
		}

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::CacheableDataChange);
		}
	}

	/**
	 * Gets heads that point to this commit
	 * 
	 * @return GitPHP_Head[] array of heads
	 */
	public function GetHeads()
	{
		return $this->GetProject()->GetHeadList()->GetCommitHeads($this);
	}

	/**
	 * Gets tags that point to this commit
	 *
	 * @return GitPHP_Tag[] array of tags
	 */
	public function GetTags()
	{
		return $this->GetProject()->GetTagList()->GetCommitTags($this);
	}

	/**
	 * Gets the tag that contains the changes in this commit
	 *
	 * @return GitPHP_Tag tag object
	 */
	public function GetContainingTag()
	{
		$tag = $this->GetContainingTagName();

		if (empty($tag))
			return null;

		return $this->GetProject()->GetTagList()->GetTag($tag);
	}

	/**
	 * Gets the name of the tag that contains the changes in this commit
	 *
	 * @return string tag name
	 */
	public function GetContainingTagName()
	{
		if (!$this->containingTagRead)
			$this->ReadContainingTag();

		return $this->containingTag;
	}

	/**
	 * Looks up the tag that contains the changes in this commit
	 */
	public function ReadContainingTag()
	{
		$this->containingTagRead = true;

		$this->containingTag = $this->strategy->LoadContainingTag($this);
	}

	/**
	 * Diffs this commit with its immediate parent
	 *
	 * @param GitPHP_GitExe $exe git executable
	 * @return GitPHP_TreeDiff Tree diff
	 */
	public function DiffToParent($exe)
	{
		return new GitPHP_TreeDiff($this->GetProject(), $exe, $this->hash);
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
		$properties = array('dataRead', 'parents', 'tree', 'author', 'authorEpoch', 'authorTimezone', 'committer', 'committerEpoch', 'committerTimezone', 'title', 'comment', 'readTree');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Commit::CacheKey($this->project->GetProject(), $this->hash);
	}

	/**
	 * Compares two commits by age
	 *
	 * @param GitPHP_Commit $a first commit
	 * @param GitPHP_Commit $b second commit
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		if ($a->GetAge() === $b->GetAge()) {
			// fall back on author epoch
			return 0 - GitPHP_Commit::CompareAuthorEpoch($a, $b);
		}
		return ($a->GetAge() < $b->GetAge() ? -1 : 1);
	}

	/**
	 * Compares two commits by author epoch
	 *
	 * @param GitPHP_Commit $a first commit
	 * @param GitPHP_Commit $b second commit
	 * @return integer comparison result
	 */
	public static function CompareAuthorEpoch($a, $b)
	{
		if ($a->GetAuthorEpoch() === $b->GetAuthorEpoch()) {
			return 0;
		}
		return ($a->GetAuthorEpoch() < $b->GetAuthorEpoch() ? -1 : 1);
	}

	/**
	 * Generates a commit cache key
	 *
	 * @param string $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		return 'project|' . $proj . '|commit|' . $hash;
	}

}
