<?php
/**
 * Constant for default minimum abbreviated hash length
 */
define('GITPHP_ABBREV_HASH_MIN', 7);

/**
 * Class for a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{

/* internal variables {{{1*/

	/**
	 * The project root
	 *
	 * @var string
	 */
	protected $projectRoot;

	/**
	 * The project
	 *
	 * @var string
	 */
	protected $project;

/* owner internal variables {{{2*/

	/**
	 * The owner
	 *
	 * @var string
	 */
	protected $owner = "";

	/**
	 * Whether the project owner has been read
	 *
	 * @var boolean
	 */
	protected $ownerRead = false;

/*}}}2*/

/* description internal variables {{{2*/

	/**
	 * The description
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Whether the description has been read from the file yet
	 *
	 * @var boolean
	 */
	protected $readDescription = false;

/*}}}2*/

	/**
	 * The category
	 *
	 * @var string
	 */
	protected $category = '';

/* epoch internal variables {{{2*/

	/**
	 * The project epoch
	 *
	 * @var int
	 */
	protected $epoch;

	/**
	 * Whether the project epoch has been read yet
	 *
	 * @var boolean
	 */
	protected $epochRead = false;

/*}}}2*/

/* HEAD internal variables {{{2*/

	/**
	 * The HEAD hash
	 *
	 * @var string
	 */
	protected $head;

	/**
	 * Whether the head ref has been read yet
	 *
	 * @var boolean
	 */
	protected $readHeadRef = false;

/*}}}*/

/* ref internal variables {{{2*/

	/**
	 * The head list for the project
	 *
	 * @var GitPHP_HeadList
	 */
	protected $headList;

	/**
	 * The tag list for the project
	 *
	 * @var GitPHP_TagList
	 */
	protected $tagList;

/*}}}2*/

/* url internal variables {{{2*/

	/**
	 * The clone url
	 *
	 * @var string
	 */
	protected $cloneUrl = null;

	/**
	 * The push url
	 *
	 * @var string
	 */
	protected $pushUrl = null;

/*}}}2*/

/* bugtracker internal variables {{{2*/

	/**
	 * The bug url
	 *
	 * @var string
	 */
	protected $bugUrl = null;

	/**
	 * The bug pattern
	 *
	 * @var string
	 */
	protected $bugPattern = null;

/*}}}2*/

	/**
	 * The website url
	 *
	 * @var string
	 */
	protected $website = null;

	/**
	 * Stores whether this project is running in compatibility mode
	 *
	 * @var boolean
	 */
	protected $compat = false;

/* hash abbreviation variables {{{2*/

	/**
	 * The hash abbreviation length
	 *
	 * @var int
	 */
	protected $abbreviateLength = null;

	/**
	 * Whether hashes should be guaranteed unique
	 *
	 * @var boolean
	 */
	protected $uniqueAbbreviation = false;

/*}}}2*/

	/**
	 * The raw git object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

/*}}}1*/

/* class methods {{{1*/

	/**
	 * Class constructor
	 *
	 * @param string $projectRoot project root
	 * @param string $project project
	 */
	public function __construct($projectRoot, $project)
	{
		$this->projectRoot = GitPHP_Util::AddSlash($projectRoot);
		$this->SetProject($project);
	}

/*}}}1*/

/* accessors {{{1*/

/* project accessors {{{2*/

	/**
	 * Gets the project
	 *
	 * @access public
	 * @return string the project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Attempts to set the project
	 *
	 * @param string $project the project
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	private function SetProject($project)
	{
		$realProjectRoot = realpath($this->projectRoot);
		$path = $this->projectRoot . $project;
		$fullPath = realpath($path);

		if (!is_dir($fullPath)) {
			throw new Exception(sprintf(__('%1$s is not a directory'), $project));
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception(sprintf(__('%1$s is not a git repository'), $project));
		}

		if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project)) {
			throw new Exception(sprintf(__('%1$s is attempting directory traversal'), $project));
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0)) {
			throw new Exception(sprintf(__('%1$s is outside of the projectroot'), $project));
		}

		$this->project = $project;

	}

/*}}}2*/

	/**
	 * Gets the project as a filename/url friendly slug
	 *
	 * @return string the slug
	 */
	public function GetSlug()
	{
		$project = $this->project;

		if (substr($project, -4) == '.git')
			$project = substr($project, 0, -4);
		
		return GitPHP_Util::MakeSlug($project);
	}

	/**
	 * Gets the full project path
	 *
	 * @return string project path
	 */
	public function GetPath()
	{
		return $this->projectRoot . $this->project;
	}

/* owner accessors {{{2 */

	/**
	 * Gets the project's owner
	 *
	 * @return string project owner
	 */
	public function GetOwner()
	{
		if (empty($this->owner) && !$this->ownerRead) {
			$this->ReadOwner();
		}
	
		return $this->owner;
	}

	/**
	 * Reads the project owner
	 */
	protected function ReadOwner()
	{
		if (empty($this->owner) && function_exists('posix_getpwuid')) {
			$uid = fileowner($this->GetPath());
			if ($uid !== false) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
				}
			}
		}

		$this->ownerRead = true;
	}

	/**
	 * Sets the project's owner (from an external source)
	 *
	 * @param string $owner the owner
	 */
	public function SetOwner($owner)
	{
		$this->owner = $owner;
	}

/*}}}2*/

/* projectroot accessors {{{2*/

	/**
	 * Gets the project root
	 *
	 * @return string the project root
	 */
	public function GetProjectRoot()
	{
		return $this->projectRoot;
	}

/*}}}2*/

/* description accessors {{{2*/

	/**
	 * Gets the project description
	 *
	 * @param $trim length to trim description to (0 for no trim)
	 * @return string project description
	 */
	public function GetDescription($trim = 0)
	{
		if (!$this->readDescription) {
			if (file_exists($this->GetPath() . '/description')) {
				$this->description = file_get_contents($this->GetPath() . '/description');
			}
			$this->readDescription = true;
		}
		
		if (($trim > 0) && (strlen($this->description) > $trim)) {
			return substr($this->description, 0, $trim) . '…';
		}

		return $this->description;
	}

	/**
	 * Overrides the project description
	 *
	 * @param string $descr description
	 */
	public function SetDescription($descr)
	{
		$this->description = $descr;
		$this->readDescription = true;
	}

/*}}}2*/

	/**
	 * Returns whether gitdaemon is allowed for this project
	 *
	 * @return boolean git-daemon-export-ok?
	 */
	public function GetDaemonEnabled()
	{
		return file_exists($this->GetPath() . '/git-daemon-export-ok');
	}

/* category accessors {{{2*/

	/**
	 * Gets the project's category
	 *
	 * @return string category
	 */
	public function GetCategory()
	{
		if (!empty($this->category)) {
			return $this->category;
		}

		return '';
	}

	/**
	 * Sets the project's category
	 *
	 * @param string $category category
	 */
	public function SetCategory($category)
	{
		$this->category = $category;
	}

/*}}}2*/

/* clone url accessors {{{2*/

	/**
	 * Gets the clone URL for this repository, if specified
	 *
	 * @return string clone url
	 */
	public function GetCloneUrl()
	{
		return $this->cloneUrl;
	}

	/**
	 * Overrides the clone URL for this repository
	 *
	 * @param string $cUrl clone url
	 */
	public function SetCloneUrl($cUrl)
	{
		$this->cloneUrl = $cUrl;
	}

/*}}}2*/

/* push url accessors {{{2*/

	/**
	 * Gets the push URL for this repository, if specified
	 *
	 * @return string push url
	 */
	public function GetPushUrl()
	{
		return $this->pushUrl;
	}

	/**
	 * Overrides the push URL for this repository
	 *
	 * @param string $pUrl push url
	 */
	public function SetPushUrl($pUrl)
	{
		$this->pushUrl = $pUrl;
	}

/*}}}2*/

/* bugtracker accessors {{{2*/

	/**
	 * Gets the bug URL for this repository, if specified
	 *
	 * @return string bug url
	 */
	public function GetBugUrl()
	{
		return $this->bugUrl;
	}

	/**
	 * Overrides the bug URL for this repository
	 *
	 * @param string $bUrl bug url
	 */
	public function SetBugUrl($bUrl)
	{
		$this->bugUrl = $bUrl;
	}

	/**
	 * Gets the bug pattern for this repository, if specified
	 *
	 * @return string bug pattern
	 */
	public function GetBugPattern()
	{
		return $this->bugPattern;
	}

	/**
	 * Overrides the bug pattern for this repository
	 *
	 * @param string $bPat bug pattern
	 */
	public function SetBugPattern($bPat)
	{
		$this->bugPattern = $bPat;
	}

/*}}}2*/

/* website accessors {{{2*/

	/**
	 * Gets the website for this repository, if specified
	 *
	 * @return string website
	 */
	public function GetWebsite()
	{
		if (!empty($this->website)) {
			return $this->website;
		}

		return null;
	}

	/**
	 * Sets the website for this repository
	 *
	 * @param string $site website
	 */
	public function SetWebsite($site)
	{
		$this->website = $site;
	}

/*}}}2*/

/* HEAD accessors {{{2*/

	/**
	 * Gets the head commit for this project
	 *
	 * Shortcut for getting the tip commit of the HEAD branch
	 *
	 * @return GitPHP_Commit head commit
	 */
	public function GetHeadCommit()
	{
		if (!$this->readHeadRef)
			$this->ReadHeadCommit();

		return $this->GetCommit($this->head);
	}

	/**
	 * Reads the head commit hash
	 */
	public function ReadHeadCommit()
	{
		$this->readHeadRef = true;

		if ($this->GetCompat()) {
			$this->ReadHeadCommitGit();
		} else {
			$this->ReadHeadCommitRaw();
		}
	}

	/**
	 * Read head commit using git executable
	 */
	private function ReadHeadCommitGit()
	{
		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		$this->head = trim(GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_REV_PARSE, $args));
	}

	/**
	 * Read head commit using raw git head pointer
	 */
	private function ReadHeadCommitRaw()
	{
		$headPointer = trim(file_get_contents($this->GetPath() . '/HEAD'));
		if (preg_match('/^([0-9A-Fa-f]{40})$/', $headPointer, $regs)) {
			/* Detached HEAD */
			$this->head = $regs[1];
		} else if (preg_match('/^ref: (.+)$/', $headPointer, $regs)) {
			/* standard pointer to head */
			$head = substr($regs[1], strlen('refs/heads/'));

			if ($this->GetHeadList()->Exists($head))
				$this->head = $this->GetHeadList()->GetHead($head)->GetHash();
		}
	}

/*}}}2*/

/* epoch accessors {{{2*/

	/**
	 * Gets this project's epoch (time of last change)
	 *
	 * @return integer timestamp
	 */
	public function GetEpoch()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return $this->epoch;
	}

	/**
	 * Gets this project's age (time since most recent change)
	 *
	 * @return integer age
	 */
	public function GetAge()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return time() - $this->epoch;
	}

	/**
	 * Reads this project's epoch (timestamp of most recent change)
	 */
	private function ReadEpoch()
	{
		$this->epochRead = true;

		if ($this->GetCompat()) {
			$this->ReadEpochGit();
		} else {
			$this->ReadEpochRaw();
		}
	}

	/**
	 * Reads this project's epoch using git executable
	 */
	private function ReadEpochGit()
	{
		$args = array();
		$args[] = '--format="%(committer)"';
		$args[] = '--sort=-committerdate';
		$args[] = '--count=1';
		$args[] = 'refs/heads';

		$epochstr = trim(GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_FOR_EACH_REF, $args));

		if (preg_match('/ (\d+) [-+][01]\d\d\d$/', $epochstr, $regs)) {
			$this->epoch = $regs[1];
		}
	}

	/**
	 * Reads this project's epoch using raw objects
	 */
	private function ReadEpochRaw()
	{
		$epoch = 0;
		foreach ($this->GetHeadList() as $headObj) {
			$commit = $headObj->GetCommit();
			if ($commit) {
				if ($commit->GetCommitterEpoch() > $epoch) {
					$epoch = $commit->GetCommitterEpoch();
				}
			}
		}
		if ($epoch > 0) {
			$this->epoch = $epoch;
		}
	}

/*}}}2*/

/* compatibility accessors {{{2*/

	/**
	 * Gets whether this project is running in compatibility mode
	 *
	 * @return boolean true if compatibilty mode
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * Sets whether this project is running in compatibility mode
	 *
	 * @param boolean true if compatibility mode
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;

		if ($this->headList)
			$this->headList->SetCompat($compat);
		if ($this->tagList)
			$this->tagList->SetCompat($compat);
	}

/*}}}2*/

/*}}}1*/

/* data loading methods {{{1*/

/* commit loading methods {{{2*/

	/**
	 * Get a commit for this project
	 *
	 * @param string $hash commit hash
	 * @return GitPHP_Commit|null commit object
	 */
	public function GetCommit($hash)
	{
		if (empty($hash))
			return null;

		if ($hash === 'HEAD')
			return $this->GetHeadCommit();

		if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {

			$key = GitPHP_Commit::CacheKey($this->project, $hash);
			$memoryCache = GitPHP_MemoryCache::GetInstance();
			$commit = $memoryCache->Get($key);

			if (!$commit) {

				$commit = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

				if ($commit) {
					$commit->SetProject($this);
				} else {
					$commit = new GitPHP_Commit($this, $hash);
				}

				$commit->SetCompat($this->GetCompat());

				$memoryCache->Set($key, $commit);

			}

			return $commit;

		}

		if (substr_compare($hash, 'refs/heads/', 0, 11) === 0) {
			$head = substr($hash, 11);
			if ($this->GetHeadList()->Exists($head))
				return $this->GetHeadList()->GetHead($head)->GetCommit();
			return null;
		} else if (substr_compare($hash, 'refs/tags/', 0, 10) === 0) {
			$tag = substr($hash, 10);
			if ($this->GetTagList()->Exists($tag)) {
				return $this->GetTagList()->GetTag($tag)->GetCommit();
			}
			return null;
		}

		if ($this->GetHeadList()->Exists($hash)) {
			return $this->GetHeadList()->GetHead($hash)->GetCommit();
		}

		if ($this->GetTagList()->Exists($hash)) {
			return $this->GetTagList()->GetTag($hash)->GetCommit();
		}

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash)) {
			return $this->GetCommit($this->ExpandHash($hash));
		}

		return null;
	}

/*}}}2*/

/* tag loading methods {{{2*/

	/**
	 * Gets the tag list
	 *
	 * @return GitPHP_TagList tag list
	 */
	public function GetTagList()
	{
		if (!$this->tagList) {
			$this->tagList = new GitPHP_TagList($this);
			$this->tagList->SetCompat($this->GetCompat());
		}

		return $this->tagList;
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

		$key = GitPHP_Tag::CacheKey($this->project, $tag);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$tagObj = $memoryCache->Get($key);

		if (!$tagObj) {
			$tagObj = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($tagObj) {
				$tagObj->SetProject($this);
			} else {
				$tagObj = new GitPHP_Tag($this, $tag, $hash);
			}

			$tagObj->SetCompat($this->GetCompat());

			$memoryCache->Set($key, $tagObj);
		}

		return $tagObj;
	}

/*}}}2*/

/* head loading methods {{{2*/

	/**
	 * Gets the head list
	 *
	 * @return GitPHP_HeadList head list
	 */
	public function GetHeadList()
	{
		if (!$this->headList) {
			$this->headList = new GitPHP_HeadList($this);
			$this->headList->SetCompat($this->GetCompat());
		}

		return $this->headList;
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

		$key = GitPHP_Head::CacheKey($this->project, $head);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$headObj = $memoryCache->Get($key);

		if (!$headObj) {
			$headObj = new GitPHP_Head($this, $head, $hash);

			$memoryCache->Set($key, $headObj);
		}

		return $headObj;
	}

/*}}}2*/

/* blob loading methods {{{2*/

	/**
	 * Gets a blob from this project
	 *
	 * @param string $hash blob hash
	 * @return GitPHP_Blob blob object
	 */
	public function GetBlob($hash)
	{
		if (empty($hash))
			return null;

		$key = GitPHP_Blob::CacheKey($this->project, $hash);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$blob = $memoryCache->Get($key);

		if (!$blob) {
			$blob = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($blob) {
				$blob->SetProject($this);
			} else {
				$blob = new GitPHP_Blob($this, $hash);
			}

			$blob->SetCompat($this->GetCompat());

			$memoryCache->Set($key, $blob);
		}

		return $blob;
	}

/*}}}2*/

/* tree loading methods {{{2*/

	/**
	 * Gets a tree from this project
	 *
	 * @param string $hash tree hash
	 * @return GitPHP_Tree tree object
	 */
	public function GetTree($hash)
	{
		if (empty($hash))
			return null;

		$key = GitPHP_Tree::CacheKey($this->project, $hash);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$tree = $memoryCache->Get($key);

		if (!$tree) {
			$tree = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if ($tree) {
				$tree->SetProject($this);
			} else {
				$tree = new GitPHP_Tree($this, $hash);
			}

			$tree->SetCompat($this->GetCompat());

			$memoryCache->Set($key, $tree);
		}

		return $tree;
	}

/*}}}2*/

/* object loader methods {{{2*/

	/**
	 * Gets the git object loader for this project
	 *
	 * @access public
	 * @return GitPHP_GitObjectLoader
	 */
	public function GetObjectLoader()
	{
		if (!$this->objectLoader) {
			$this->objectLoader = new GitPHP_GitObjectLoader($this);
		}

		return $this->objectLoader;
	}

/*}}}2*/

/* hash management methods {{{2*/

	/**
	 * Gets the hash abbreviation length
	 *
	 * @return int abbreviate length
	 */
	public function GetAbbreviateLength()
	{
		return $this->abbreviateLength;
	}

	/**
	 * Sets the hash abbreviation length
	 *
	 * @param int $length abbreviate length
	 */
	public function SetAbbreviateLength($length)
	{
		$this->abbreviateLength = $length;
	}

	/**
	 * Gets whether abbreviated hashes should be guaranteed unique
	 *
	 * @return bool true if hashes are guaranteed unique
	 */
	public function GetUniqueAbbreviation()
	{
		return $this->uniqueAbbreviation;
	}

	/**
	 * Sets whether abbreviated hashes should be guaranteed unique
	 *
	 * @param bool true if hashes should be guaranteed unique
	 */
	public function SetUniqueAbbreviation($unique)
	{
		$this->uniqueAbbreviation = $unique;
	}

	/**
	 * Calculates the unique abbreviated hash for a full hash
	 *
	 * @param string $hash full hash
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($hash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			return $hash;
		}

		if ($this->GetCompat()) {
			return $this->AbbreviateHashGit($hash);
		} else {
			return $this->AbbreviateHashRaw($hash);
		}
	}

	/**
	 * Abbreviates a hash using the git executable
	 *
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	private function AbbreviateHashGit($hash)
	{
		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%h';
		$args[] = $hash;

		$abbrevData = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_REV_LIST, $args));
		if (empty($abbrevData[0])) {
			return $hash;
		}
		if (substr_compare(trim($abbrevData[0]), 'commit', 0, 6) !== 0) {
			return $hash;
		}

		if (empty($abbrevData[1])) {
			return $hash;
		}

		return trim($abbrevData[1]);
	}

	/**
	 * Abbreviates a hash using raw git objects
	 *
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	private function AbbreviateHashRaw($hash)
	{
		$abbrevLen = GITPHP_ABBREV_HASH_MIN;

		if ($this->abbreviateLength > 0) {
			$abbrevLen = max(4, min($this->abbreviateLength, 40));
		}

		$prefix = substr($hash, 0, $abbrevLen);

		if (!$this->uniqueAbbreviation) {
			return $prefix;
		}

		return $this->GetObjectLoader()->EnsureUniqueHash($hash, $prefix);
	}

	/**
	 * Finds the full hash for an abbreviated hash
	 *
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($abbrevHash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{4,39}/', $abbrevHash))) {
			return $abbrevHash;
		}

		if ($this->GetCompat()) {
			return $this->ExpandHashGit($abbrevHash);
		}  else {
			return $this->GetObjectLoader()->ExpandHash($abbrevHash);
		}
	}

	/**
	 * Expands a hash using the git executable
	 *
	 * @param string $abbrevHash
	 * @return string full hash
	 */
	private function ExpandHashGit($abbrevHash)
	{
		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%H';
		$args[] = $abbrevHash;

		$fullData = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_REV_LIST, $args));
		if (empty($fullData[0])) {
			return $abbrevHash;
		}
		if (substr_compare(trim($fullData[0]), 'commit', 0, 6) !== 0) {
			return $abbrevHash;
		}

		if (empty($fullData[1])) {
			return $abbrevHash;
		}

		return trim($fullData[1]);
	}

/*}}}2*/

/*}}}1*/

/* static utilities {{{1*/

	/**
	 * Compares two projects by project name
	 *
	 * @param GitPHP_Project $a first project
	 * @param GitPHP_Project $b second project
	 * @return integer comparison result
	 */
	public static function CompareProject($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetProject(), $b->GetProject());
	}

	/**
	 * Compares two projects by description
	 *
	 * @param GitPHP_Project $a first project
	 * @param GitPHP_Project $b second project
	 * @return integer comparison result
	 */
	public static function CompareDescription($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetDescription(), $b->GetDescription());
	}

	/**
	 * Compares two projects by owner
	 *
	 * @param GitPHP_Project $a first project
	 * @param GitPHP_Project $b second project
	 * @return integer comparison result
	 */
	public static function CompareOwner($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetOwner(), $b->GetOwner());
	}

	/**
	 * Compares two projects by age
	 *
	 * @param GitPHP_Project $a first project
	 * @param GitPHP_Project $b second project
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		if ($a->GetAge() === $b->GetAge())
			return 0;
		return ($a->GetAge() < $b->GetAge() ? -1 : 1);
	}

/*}}}1*/

}
