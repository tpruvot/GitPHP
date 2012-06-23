<?php
/**
 * Represents a single commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Commit extends GitPHP_GitObject
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
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash object hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * Gets the hash for this commit (overrides base)
	 *
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string object hash
	 */
	public function GetHash($abbreviate = false)
	{
		if ($this->compat && $abbreviate) {
			// abbreviated hash is loaded as part of commit data in compat mode
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
		if (!$this->dataRead)
			$this->ReadData();

		if (isset($this->parents[0])) {
			return $this->GetProject()->GetCommit($this->parents[0]);
		}

		return null;
	}

	/**
	 * Gets an array of parent objects for this commit
	 *
	 * @return GitPHP_Commit[] array of commit objects
	 */
	public function GetParents()
	{
		if (!$this->dataRead)
			$this->ReadData();

		$parents = array();
		foreach ($this->parents as $parent) {
			$parents[] = $this->GetProject()->GetCommit($parent);
		}

		return $parents;
	}

	/**
	 * Gets the tree for this commit
	 *
	 * @return GitPHP_Tree tree object
	 */
	public function GetTree()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (empty($this->tree))
			return null;

		$tree = $this->GetProject()->GetObjectManager()->GetTree($this->tree);
		if ($tree) {
			$tree->SetCommitHash($this->hash);
			$tree->SetPath(null);
		}

		return $tree;
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
	 * @return string author timezone
	 */
	public function GetAuthorTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

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
	 * @return string author timezone
	 */
	public function GetCommitterTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

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

		$lines = null;

		if ($this->compat) {

			/* get data from git_rev_list */
			$args = array();
			$args[] = '--header';
			$args[] = '--parents';
			$args[] = '--max-count=1';
			$args[] = '--abbrev-commit';
			$args[] = $this->hash;
			$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_REV_LIST, $args);

			$lines = explode("\n", $ret);

			if (!isset($lines[0]))
				return;

			/* In case we returned something unexpected */
			$tok = strtok($lines[0], ' ');
			if ((strlen($tok) == 0) || (substr_compare($this->hash, $tok, 0, strlen($tok)) !== 0)) {
				return;
			}
			$this->abbreviatedHash = $tok;
			$this->abbreviatedHashLoaded = true;

			array_shift($lines);

		} else {
			
			$data = $this->GetProject()->GetObjectLoader()->GetObject($this->hash);
			if (empty($data))
				return;

			$lines = explode("\n", $data);

		}

		$linecount = count($lines);
		$i = 0;
		$encoding = null;

		/* Commit header */
		for ($i = 0; $i < $linecount; $i++) {
			$line = $lines[$i];
			if (preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Tree */
				$this->tree = $regs[1];
			} else if (preg_match('/^parent ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Parent */
				$this->parents[] = $regs[1];
			} else if (preg_match('/^author (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* author data */
				$this->author = $regs[1];
				$this->authorEpoch = $regs[2];
				$this->authorTimezone = $regs[3];
			} else if (preg_match('/^committer (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* committer data */
				$this->committer = $regs[1];
				$this->committerEpoch = $regs[2];
				$this->committerTimezone = $regs[3];
			} else if (preg_match('/^encoding (.+)$/', $line, $regs)) {
				$gitEncoding = trim($regs[1]);
				if ((strlen($gitEncoding) > 0) && function_exists('mb_list_encodings')) {
					$supportedEncodings = mb_list_encodings();
					$encIdx = array_search(strtolower($gitEncoding), array_map('strtolower', $supportedEncodings));
					if ($encIdx !== false) {
						$encoding = $supportedEncodings[$encIdx];
					}
				}
				$encoding = trim($regs[1]);
			} else if (strlen($line) == 0) {
				break;
			}
		}
		
		/* Commit body */
		for ($i += 1; $i < $linecount; $i++) {
			$trimmed = trim($lines[$i]);

			if ((strlen($trimmed) > 0) && (strlen($encoding) > 0) && function_exists('mb_convert_encoding')) {
				$trimmed = mb_convert_encoding($trimmed, 'UTF-8', $encoding);
			}

			if (empty($this->title) && (strlen($trimmed) > 0))
				$this->title = $trimmed;
			if (!empty($this->title)) {
				if ((strlen($trimmed) > 0) || ($i < ($linecount-1)))
					$this->comment[] = $trimmed;
			}
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * Gets heads that point to this commit
	 * 
	 * @return GitPHP_Head[] array of heads
	 */
	public function GetHeads()
	{
		$heads = array();

		$projectRefs = $this->GetProject()->GetHeadList()->GetHeads();

		foreach ($projectRefs as $ref) {
			if ($ref->GetHash() == $this->hash) {
				$heads[] = $ref;
			}
		}

		return $heads;
	}

	/**
	 * Gets tags that point to this commit
	 *
	 * @return GitPHP_Tag[] array of tags
	 */
	public function GetTags()
	{
		$tags = array();

		$projectRefs = $this->GetProject()->GetTagList()->GetTags();

		foreach ($projectRefs as $ref) {
			if (($ref->GetType() == 'tag') || ($ref->GetType() == 'commit')) {
				if ($ref->GetCommit()->GetHash() === $this->hash) {
					$tags[] = $ref;
				}
			}
		}

		return $tags;
	}

	/**
	 * Gets the tag that contains the changes in this commit
	 *
	 * @return GitPHP_Tag tag object
	 */
	public function GetContainingTag()
	{
		if (!$this->containingTagRead)
			$this->ReadContainingTag();

		if (empty($this->containingTag))
			return null;

		return $this->GetProject()->GetTagList()->GetTag($this->containingTag);
	}

	/**
	 * Looks up the tag that contains the changes in this commit
	 */
	public function ReadContainingTag()
	{
		$this->containingTagRead = true;

		$args = array();
		$args[] = '--tags';
		$args[] = $this->hash;
		$revs = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_NAME_REV, $args));

		foreach ($revs as $revline) {
			if (preg_match('/^([0-9a-fA-F]{40})\s+tags\/(.+)(\^[0-9]+|\~[0-9]+)$/', $revline, $regs)) {
				if ($regs[1] == $this->hash) {
					$this->containingTag = $regs[2];
					break;
				}
			}
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * Diffs this commit with its immediate parent
	 *
	 * @return GitPHP_TreeDiff Tree diff
	 */
	public function DiffToParent()
	{
		return new GitPHP_TreeDiff($this->GetProject(), $this->hash);
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
			return GitPHP_Commit::CompareAuthorEpoch($a, $b);
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
		return ($a->GetAuthorEpoch() > $b->GetAuthorEpoch() ? -1 : 1);
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
