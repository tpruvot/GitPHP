<?php
/**
 * GitPHP Project
 * 
 * Represents a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

define('GITPHP_ABBREV_HASH_MIN', 7);

/**
 * Project class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{

/* internal variables {{{1*/

	/**
	 * projectRoot
	 *
	 * Stores the project root internally
	 *
	 * @access protected
	 */
	protected $projectRoot;

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

/* owner internal variables {{{2*/

	/**
	 * owner
	 *
	 * Stores the owner internally
	 *
	 * @access protected
	 */
	protected $owner = "";

	/**
	 * ownerRead
	 *
	 * Stores whether the file owner has been read
	 *
	 * @access protected
	 */
	protected $ownerRead = false;

/*}}}2*/

/* description internal variables {{{2*/

	/**
	 * description
	 *
	 * Stores the description internally
	 *
	 * @access protected
	 */
	protected $description;

	/**
	 * readDescription
	 *
	 * Stores whether the description has been
	 * read from the file yet
	 *
	 * @access protected
	 */
	protected $readDescription = false;

/*}}}2*/

	/**
	 * category
	 *
	 * Stores the category internally
	 *
	 * @access protected
	 */
	protected $category = '';

/* epoch internal variables {{{2*/

	/**
	 * epoch
	 *
	 * Stores the project epoch internally
	 *
	 * @access protected
	 */
	protected $epoch;

	/**
	 * epochRead
	 *
	 * Stores whether the project epoch has been read yet
	 *
	 * @access protected
	 */
	protected $epochRead = false;

/*}}}2*/

/* HEAD internal variables {{{2*/

	/**
	 * head
	 *
	 * Stores the head hash internally
	 *
	 * @access protected
	 */
	protected $head;

	/**
	 * readHeadRef
	 *
	 * Stores whether the head ref has been read yet
	 *
	 * @access protected
	 */
	protected $readHeadRef = false;

/*}}}*/

/* ref internal variables {{{2*/

	/**
	 * headList
	 *
	 * Stores the head list for the project
	 *
	 * @access protected
	 */
	protected $headList;

	/**
	 * tagList
	 *
	 * Stores the tag list for the project
	 *
	 * @access protected
	 */
	protected $tagList;

/*}}}2*/

/* url internal variables {{{2*/

	/**
	 * cloneUrl
	 *
	 * Stores the clone url internally
	 *
	 * @access protected
	 */
	protected $cloneUrl = null;

	/**
	 * pushUrl
	 *
	 * Stores the push url internally
	 *
	 * @access protected
	 */
	protected $pushUrl = null;

/*}}}2*/

/* bugtracker internal variables {{{2*/

	/**
	 * bugUrl
	 *
	 * Stores the bug url internally
	 *
	 * @access protected
	 */
	protected $bugUrl = null;

	/**
	 * bugPattern
	 *
	 * Stores the bug pattern internally
	 *
	 * @access protected
	 */
	protected $bugPattern = null;

/*}}}2*/

	/**
	 * website
	 *
	 * Stores the website url internally
	 *
	 * @access protected
	 */
	protected $website = null;

	/**
	 * compat
	 *
	 * Stores whether this project is running
	 * in compatibility mode
	 *
	 * @access protected
	 */
	protected $compat = false;

/* hash abbreviation variables {{{2*/

	/**
	 * abbreviateLength
	 *
	 * Stores the hash abbreviation length internally
	 *
	 * @access protected
	 */
	protected $abbreviateLength = null;

	/**
	 * uniqueAbbreviation
	 *
	 * Stores whether hashes should be guaranteed unique
	 *
	 * @access protected
	 */
	protected $uniqueAbbreviation = false;

/*}}}2*/

	/**
	 * objectLoader
	 *
	 * Stores the raw git object loader
	 *
	 * @access protected
	 */
	protected $objectLoader;

/*}}}1*/

/* class methods {{{1*/

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access public
	 * @param string $projectRoot project root
	 * @param string $project project
	 * @throws Exception if project is invalid or outside of projectroot
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
	 * GetProject
	 *
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
	 * SetProject
	 *
	 * Attempts to set the project
	 *
	 * @access private
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
	 * GetSlug
	 *
	 * Gets the project as a filename/url friendly slug
	 *
	 * @access public
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
	 * GetPath
	 *
	 * Gets the full project path
	 *
	 * @access public
	 * @return string project path
	 */
	public function GetPath()
	{
		return $this->projectRoot . $this->project;
	}

/* owner accessors {{{2 */

	/**
	 * GetOwner
	 *
	 * Gets the project's owner
	 *
	 * @access public
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
	 * ReadOwner
	 *
	 * Reads the project owner
	 *
	 * @access protected
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
	 * SetOwner
	 *
	 * Sets the project's owner (from an external source)
	 *
	 * @access public
	 * @param string $owner the owner
	 */
	public function SetOwner($owner)
	{
		$this->owner = $owner;
	}

/*}}}2*/

/* projectroot accessors {{{2*/

	/**
	 * GetProjectRoot
	 *
	 * Gets the project root
	 *
	 * @access public
	 * @return string the project root
	 */
	public function GetProjectRoot()
	{
		return $this->projectRoot;
	}

/*}}}2*/

/* description accessors {{{2*/

	/**
	 * GetDescription
	 *
	 * Gets the project description
	 *
	 * @access public
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
	 * SetDescription
	 *
	 * Overrides the project description
	 *
	 * @access public
	 * @param string $descr description
	 */
	public function SetDescription($descr)
	{
		$this->description = $descr;
		$this->readDescription = true;
	}

/*}}}2*/

	/**
	 * GetDaemonEnabled
	 *
	 * Returns whether gitdaemon is allowed for this project
	 *
	 * @access public
	 * @return boolean git-daemon-export-ok?
	 */
	public function GetDaemonEnabled()
	{
		return file_exists($this->GetPath() . '/git-daemon-export-ok');
	}

/* category accessors {{{2*/

	/**
	 * GetCategory
	 *
	 * Gets the project's category
	 *
	 * @access public
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
	 * SetCategory
	 * 
	 * Sets the project's category
	 *
	 * @access public
	 * @param string $category category
	 */
	public function SetCategory($category)
	{
		$this->category = $category;
	}

/*}}}2*/

/* clone url accessors {{{2*/

	/**
	 * GetCloneUrl
	 *
	 * Gets the clone URL for this repository, if specified
	 *
	 * @access public
	 * @return string clone url
	 */
	public function GetCloneUrl()
	{
		return $this->cloneUrl;
	}

	/**
	 * SetCloneUrl
	 *
	 * Overrides the clone URL for this repository
	 *
	 * @access public
	 * @param string $cUrl clone url
	 */
	public function SetCloneUrl($cUrl)
	{
		$this->cloneUrl = $cUrl;
	}

/*}}}2*/

/* push url accessors {{{2*/

	/**
	 * GetPushUrl
	 *
	 * Gets the push URL for this repository, if specified
	 *
	 * @access public
	 * @return string push url
	 */
	public function GetPushUrl()
	{
		return $this->pushUrl;
	}

	/**
	 * SetPushUrl
	 *
	 * Overrides the push URL for this repository
	 *
	 * @access public
	 * @param string $pUrl push url
	 */
	public function SetPushUrl($pUrl)
	{
		$this->pushUrl = $pUrl;
	}

/*}}}2*/

/* bugtracker accessors {{{2*/

	/**
	 * GetBugUrl
	 *
	 * Gets the bug URL for this repository, if specified
	 *
	 * @access public
	 * @return string bug url
	 */
	public function GetBugUrl()
	{
		return $this->bugUrl;
	}

	/**
	 * SetBugUrl
	 *
	 * Overrides the bug URL for this repository
	 *
	 * @access public
	 * @param string $bUrl bug url
	 */
	public function SetBugUrl($bUrl)
	{
		$this->bugUrl = $bUrl;
	}

	/**
	 * GetBugPattern
	 *
	 * Gets the bug pattern for this repository, if specified
	 *
	 * @access public
	 * @return string bug pattern
	 */
	public function GetBugPattern()
	{
		return $this->bugPattern;
	}

	/**
	 * SetBugPattern
	 *
	 * Overrides the bug pattern for this repository
	 *
	 * @access public
	 * @param string $bPat bug pattern
	 */
	public function SetBugPattern($bPat)
	{
		$this->bugPattern = $bPat;
	}

/*}}}2*/

/* website accessors {{{2*/

	/**
	 * GetWebsite
	 *
	 * Gets the website for this repository, if specified
	 *
	 * @access public
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
	 * SetWebsite
	 *
	 * Sets the website for this repository
	 *
	 * @access public
	 * @param string $site website
	 */
	public function SetWebsite($site)
	{
		$this->website = $site;
	}

/*}}}2*/

/* HEAD accessors {{{2*/

	/**
	 * GetHeadCommit
	 *
	 * Gets the head commit for this project
	 * Shortcut for getting the tip commit of the HEAD branch
	 *
	 * @access public
	 * @return mixed head commit
	 */
	public function GetHeadCommit()
	{
		if (!$this->readHeadRef)
			$this->ReadHeadCommit();

		return $this->GetCommit($this->head);
	}

	/**
	 * ReadHeadCommit
	 *
	 * Reads the head commit hash
	 *
	 * @access protected
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
	 * ReadHeadCommitGit
	 *
	 * Read head commit using git executable
	 *
	 * @access private
	 */
	private function ReadHeadCommitGit()
	{
		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		$this->head = trim(GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_REV_PARSE, $args));
	}

	/**
	 * ReadHeadCommitRaw
	 *
	 * Read head commit using raw git head pointer
	 *
	 * @access private
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
	 * GetEpoch
	 *
	 * Gets this project's epoch
	 * (time of last change)
	 *
	 * @access public
	 * @return integer timestamp
	 */
	public function GetEpoch()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return $this->epoch;
	}

	/**
	 * GetAge
	 *
	 * Gets this project's age
	 * (time since most recent change)
	 *
	 * @access public
	 * @return integer age
	 */
	public function GetAge()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return time() - $this->epoch;
	}

	/**
	 * ReadEpoch
	 *
	 * Reads this project's epoch
	 * (timestamp of most recent change)
	 *
	 * @access private
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
	 * ReadEpochGit
	 *
	 * Reads this project's epoch using git executable
	 *
	 * @access private
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
	 * ReadEpochRaw
	 *
	 * Reads this project's epoch using raw objects
	 *
	 * @access private
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
	 * GetCompat
	 *
	 * Gets whether this project is running in compatibility mode
	 *
	 * @access public
	 * @return boolean true if compatibilty mode
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * SetCompat
	 *
	 * Sets whether this project is running in compatibility mode
	 *
	 * @access public
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
	 * GetCommit
	 *
	 * Get a commit for this project
	 *
	 * @access public
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

				if (!$commit) {
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
	 * GetTagList
	 *
	 * Gets the tag list
	 *
	 * @access public
	 * @return mixed tag list
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
	 * GetTag
	 *
	 * Gets a single tag
	 *
	 * @access public
	 * @param string $tag tag to find
	 * @return mixed tag object
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

			if (!$tagObj) {
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
	 * GetHeadList
	 *
	 * Gets the head list
	 *
	 * @access public
	 * @return mixed head list
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
	 * GetHead
	 *
	 * Gets a single head
	 *
	 * @access public
	 * @param string $head head to find
	 * @return mixed head object
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
	 * GetBlob
	 *
	 * Gets a blob from this project
	 *
	 * @access public
	 * @param string $hash blob hash
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

			if (!$blob) {
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
	 * GetTree
	 *
	 * Gets a tree from this project
	 *
	 * @access public
	 * @param string $hash tree hash
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

			if (!$tree) {
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
	 * GetObjectLoader
	 *
	 * Gets the git object loader for this project
	 *
	 * @access public
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
	 * GetAbbreviateLength
	 *
	 * Gets the hash abbreviation length
	 *
	 * @access public
	 * @return int abbreviate length
	 */
	public function GetAbbreviateLength()
	{
		return $this->abbreviateLength;
	}

	/**
	 * SetAbbreviateLength
	 *
	 * Sets the hash abbreviation length
	 *
	 * @access public
	 * @param int $length abbreviate length
	 */
	public function SetAbbreviateLength($length)
	{
		$this->abbreviateLength = $length;
	}

	/**
	 * GetUniqueAbbreviation
	 *
	 * Gets whether abbreviated hashes should be guaranteed unique
	 *
	 * @access public
	 * @return bool true if hashes are guaranteed unique
	 */
	public function GetUniqueAbbreviation()
	{
		return $this->uniqueAbbreviation;
	}

	/**
	 * SetUniqueAbbreviation
	 *
	 * Sets whether abbreviated hashes should be guaranteed unique
	 *
	 * @access public
	 * @param bool true if hashes should be guaranteed unique
	 */
	public function SetUniqueAbbreviation($unique)
	{
		$this->uniqueAbbreviation = $unique;
	}

	/**
	 * AbbreviateHash
	 *
	 * Calculates the unique abbreviated hash for a full hash
	 *
	 * @param string $hash hash to abbreviate
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
	 * AbbreviateHashGit
	 *
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
	 * AbbreviateHashRaw
	 *
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
	 * ExpandHash
	 *
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
	 * ExpandHashGit
	 *
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
	 * CompareProject
	 *
	 * Compares two projects by project name
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
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
	 * CompareDescription
	 *
	 * Compares two projects by description
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
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
	 * CompareOwner
	 *
	 * Compares two projects by owner
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
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
	 * CompareAge
	 *
	 * Compares two projects by age
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
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
