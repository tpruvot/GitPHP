<?php
/**
 * Represents a single commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Commit extends GitPHP_GitObject implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
{

	/**
	 * Indicates whether data for this commit has been read
	 */
	protected $dataRead = false;

	/**
	 * Array of parent commits
	 */
	protected $parents = array();

	/**
	 * Tree hash for this commit
	 */
	protected $tree;

	/**
	 * Author for this commit
	 */
	protected $author;

	/**
	 * Author's epoch
	 */
	protected $authorEpoch;

	/**
	 * Author's timezone
	 */
	protected $authorTimezone;

	/**
	 * Committer for this commit
	 */
	protected $committer;

	/**
	 * Committer's epoch
	 */
	protected $committerEpoch;

	/**
	 * Committer's timezone
	 */
	protected $committerTimezone;

	/**
	 * Stores the commit title
	 */
	protected $title;

	/**
	 * Stores the commit comment
	 */
	protected $comment = array();

	/**
	 * Stores whether tree filenames have been read
	 */
	protected $readTree = false;

	/**
	 * Stores blob hash to path mappings
	 */
	protected $blobPaths = array();

	/**
	 * Stores tree hash to path mappings
	 */
	protected $treePaths = array();

	/**
	 * Stores whether hash paths have been read
	 */
	protected $hashPathsRead = false;

	/**
	 * Stores the tag containing the changes in this commit
	 */
	protected $containingTag = null;

	/**
	 * Stores whether the containing tag has been looked up
	 */
	protected $containingTagRead = false;

	/**
	 * Observers
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Instantiates object
	 *
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git object
	 * @throws Exception exception on invalid hash
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
		if ($this->GetProject()->GetCompat() && $abbreviate) {
			// abbreviated hash is loaded as part of commit data in compat mode
			if (!$this->dataRead)
				$this->ReadData();
		}

		return parent::GetHash($abbreviate);
	}

	/**
	 * Gets the main parent of this commit
	 *
	 * @return mixed commit object for parent
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
	 * @return mixed array of commit objects
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

		$tree = $this->GetProject()->GetTree($this->tree);
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
	 * @return array lines of comment
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
	 * @return array matching lines of comment
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

		if ($this->GetProject()->GetCompat()) {

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

			$data = $this->GetProject()->GetObject($this->hash);
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

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::CacheableDataChange);
		}
	}

	/**
	 * Gets heads that point to this commit
	 *
	 * @return array array of heads
	 */
	public function GetHeads()
	{
		$heads = array();

		$projectRefs = $this->GetProject()->GetRefs('heads');

		foreach ($projectRefs as $ref) {
			if ($ref->GetHash() == $this->hash) {
				$heads[] = $ref;
			}
		}

		return $heads;
	}

	/**
	 * Gets remote heads that point to this commit
	 *
	 * @return array array of heads
	 */
	public function GetRemoteHeads()
	{
		$heads = array();

		$projectRefs = $this->GetProject()->GetRefs('remotes');

		foreach ($projectRefs as $ref => $hash) {
			if ($hash == $this->hash) {
				$heads[] = $ref;
			}
		}

		return $heads;
	}

	/**
	 * Gets tags that point to this commit
	 *
	 * @return array array of tags
	 */
	public function GetTags()
	{
		$tags = array();

		$projectRefs = $this->GetProject()->GetRefs('tags');

		foreach ($projectRefs as $ref) {
			if (($ref->GetType() == 'tag') || ($ref->GetType() == 'commit')) {
				if ($ref->GetCommit() && $ref->GetCommit()->GetHash() === $this->hash) {
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

		//to backport...
		//$this->containingTag = $this->strategy->LoadContainingTag($this);

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
	}

	/**
	 * Diffs this commit with its immediate parent
	 *
	 * @return mixed Tree diff
	 */
	public function DiffToParent()
	{
		return new GitPHP_TreeDiff($this->GetProject(), $this->hash);
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

		if ($this->GetProject()->GetCompat()) {
			$this->ReadHashPathsGit();
		} else {
			$this->ReadHashPathsRaw($this->GetTree());
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
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
	 * Reads hash to path mappings using raw objects
	 */
	private function ReadHashPathsRaw($tree)
	{
		if (!$tree) {
			return;
		}

		$contents = $tree->GetContents();

		foreach ($contents as $obj) {
			if ($obj instanceof GitPHP_Blob) {
				$hash = $obj->GetHash();
				$path = $obj->GetPath();
				$this->blobPaths[trim($path)] = $hash;
			} else if ($obj instanceof GitPHP_Tree) {
				$hash = $obj->GetHash();
				$path = $obj->GetPath();
				$this->treePaths[trim($path)] = $hash;
				$this->ReadHashPathsRaw($obj);
			}
		}
	}

	/**
	 * Returns array of objects matching pattern
	 *
	 * @param string $pattern pattern to find
	 * @return array array of objects
	 */
	public function SearchFilenames($pattern)
	{
		if (empty($pattern))
			return;

		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		$results = array();
		$usedTrees = array();
		$usedBlobs = array();

		foreach ($this->treePaths as $path => $hash) {
			if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
				$obj = $this->GetProject()->GetTree($hash);
				if (isset($usedTrees[$hash])) {
					$obj = clone $obj;
				} else {
					$usedTrees[$hash] = 1;
				}
				$obj->SetCommitHash($this->hash);
				$obj->SetPath($path);
				$results[$path] = $obj;
			}
		}

		foreach ($this->blobPaths as $path => $hash) {
			if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
				$obj = $this->GetProject()->GetBlob($hash);
				if (isset($usedBlobs[$hash])) {
					$obj = clone $obj;
				} else {
					$usedBlobs[$hash] = 1;
				}
				$obj->SetCommitHash($this->hash);
				$obj->SetPath($path);
				$results[$path] = $obj;
			}
		}

		ksort($results);

		return $results;
	}

	/**
	 * Searches for a pattern in file contents
	 *
	 * @param string $pattern pattern to search for
	 * @return array multidimensional array of results
	 */
	public function SearchFileContents($pattern)
	{
		if (empty($pattern))
			return;

		$args = array();
		$args[] = '-I';
		$args[] = '--full-name';
		$args[] = '--ignore-case';
		$args[] = '-n';
		$args[] = '-e';
		$args[] = '"' . addslashes($pattern) . '"';
		$args[] = $this->hash;

		$lines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_GREP, $args));

		$results = array();

		foreach ($lines as $line) {
			if (preg_match('/^[^:]+:([^:]+):([0-9]+):(.+)$/', $line, $regs)) {
				if (!isset($results[$regs[1]]['object'])) {
					$hash = $this->PathToHash($regs[1]);
					if (!empty($hash)) {
						$obj = $this->GetProject()->GetBlob($hash);
						$obj->SetCommitHash($this->hash);
						$results[$regs[1]]['object'] = $obj;
					}
				}
				$results[$regs[1]]['lines'][(int)($regs[2])] = $regs[3];
			}
		}

		return $results;
	}

	/**
	 * Searches filenames and file contents for a pattern
	 *
	 * @param string $pattern pattern to search
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of results
	 */
	public function SearchFiles($pattern, $count = 100, $skip = 0)
	{
		if (empty($pattern))
			return;

		$grepresults = $this->SearchFileContents($pattern);

		$nameresults = $this->SearchFilenames($pattern);

		/* Merge the results together */
		foreach ($nameresults as $path => $obj) {
			if (!isset($grepresults[$path]['object'])) {
				$grepresults[$path]['object'] = $obj;
			}
		}

		ksort($grepresults);

		return array_slice($grepresults, $skip, $count, true);
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
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('dataRead', 'parents', 'tree', 'author', 'authorEpoch', 'authorTimezone', 'committer', 'committerEpoch', 'committerTimezone', 'title', 'comment', 'readTree', 'blobPaths', 'treePaths', 'hashPathsRead');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Commit::CacheKey($this->project, $this->hash);
	}

	/**
	 * Compares two commits by age
	 *
	 * @param mixed $a first commit
	 * @param mixed $b second commit
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
	 * @param mixed $a first commit
	 * @param mixed $b second commit
	 * @return integer comparison result
	 */
	public static function CompareAuthorEpoch($a, $b)
	{
		// PHP Parsing of git history require this.
		if (!$a->GetProject()->GetCompat()) {
			if ($a->GetParent()
			    && $a->GetParent()->GetHash() == $b->GetHash())
				return -1;
			if ($b->GetParent()
			    && $a->GetHash() == $b->GetParent()->GetHash())
				return 1;
		}
		if ($a->GetAuthorEpoch() === $b->GetAuthorEpoch()) {
			return 0;
		}
		return ($a->GetAuthorEpoch() < $b->GetAuthorEpoch() ? 1 : -1);
	}

	/**
	 * Generates a commit cache key
	 *
	 * @param mixed $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		if (is_object($proj))
			return 'project|' . $proj->GetProject() . '|commit|' . $hash;

		return 'project|' . $proj . '|commit|' . $hash;
	}

}
