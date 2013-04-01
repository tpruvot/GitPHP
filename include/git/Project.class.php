<?php
/**
 * Represents a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{
	const ABBREV_HASH_MIN = 7;

	/**
	 * Stores the project root internally
	 */
	protected $projectRoot;

	/**
	 * Stores the project internally
	 */
	protected $project;

	/**
	 * Stores the owner internally
	 */
	protected $owner = "";

	/**
	 * Stores whether the file owner has been read
	 */
	protected $ownerRead = false;

	/**
	 * Stores the description internally
	 */
	protected $description;

	/**
	 * Stores whether the description has been
	 * read from the file yet
	 */
	protected $readDescription = false;

	/**
	 * Stores the category internally
	 */
	protected $category = '';

	public $categoryAge = 0;

	/**
	 * Stores the project epoch internally
	 */
	protected $epoch;

	/**
	 * Stores whether the project epoch has been read yet
	 */
	protected $epochRead = false;

	/**
	 * Stores the head hash internally
	 */
	protected $head;

	/**
	 * Stores whether the head ref has been read yet
	 * @var boolean
	 */
	protected $readHeadRef = false;

	/**
	 * The head list for the project
	 * @var GitPHP_HeadList
	 */
	protected $headList;

	/**
	 * The tracked remote heads
	 * @var GitPHP_RemoteHeadList
	 */
	protected $remoteHeadList;

	/**
	 * The tag list for the project
	 * @var GitPHP_TagList
	 */
	protected $tagList;

	/**
	 * Stores whether refs have been read yet
	 */
	protected $readRefs = false;

	/**
	 * Stores the clone url internally
	 */
	protected $cloneUrl = null;

	/**
	 * Stores the push url internally
	 */
	protected $pushUrl = null;

	/**
	 * Stores the bug url internally
	 */
	protected $bugUrl = null;

	/**
	 * Stores the bug pattern internally
	 */
	protected $bugPattern = null;

	/**
	 * Stores the website url internally
	 */
	protected $website = null;

	/**
	 * Stores whether this project is running in compatibility mode
	 */
	protected $compat = false;

	/**
	 * The hash abbreviation length
	 *
	 * @var int
	 */
	protected $abbreviateLength = null;

	/**
	 * Whether hashes should be guaranteed unique
	 */
	protected $uniqueAbbreviation = false;

	/**
	 * Stores the list of packs
	 */
	protected $packs = array();

	/**
	 * Stores whether packs have been read
	 */
	protected $packsRead = false;

	/**
	 * Stores the config reader internally
	 */
	protected $config = null;

	/**
	 * The git object manager
	 */
	protected $objectManager;

	/**
	 * Stores the raw git object loader
	 */
	protected $objectLoader;

	/**
	 * .repo folders (by tpruvot)
	 */
	public $isAndroidRepo = false;
	public $repoRemote = "origin";
	public $repoBranch = "master";

	public $showRemotes = false;

	/**
	 * Class constructor
	 *
	 * @param string $projectRoot project root
	 * @param string $project project
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	public function __construct($projectRoot, $project)
	{
		$this->projectRoot = GitPHP_Util::AddSlash($projectRoot);
		$this->SetProject($project);

		if (is_file(GitPHP_Util::AddSlash($this->GetPath()) . '.repopickle_config')) {
			//.repo projects doesn't store refs/heads
			$this->isAndroidRepo = true;
			$this->showRemotes = true;
		} else {
			$this->showRemotes = GitPHP_Config::GetInstance()->GetValue('showremotes', false);
		}
	}

	/**
	 * Gets the project
	 *
	 * @return string the project
	 */
	public function GetProject($urlencode='')
	{
		if ($urlencode == 'f') {
			return GitPHP_Util::UrlEncodeFilePath($this->project);
		}
		return $this->project;
	}

	/**
	 * Attempts to set the project
	 *
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	private function SetProject($project)
	{
		$realProjectRoot = realpath($this->projectRoot);
		$path = $this->projectRoot . $project;
		$fullPath = realpath($path);

		if (!is_dir($fullPath . '/.')) {
			throw new GitPHP_InvalidDirectoryException($fullPath);
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new GitPHP_InvalidGitRepositoryException($project);
		}

		if (GitPHP_Config::GetInstance()->GetValue('projectroot') != '/') {

			if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project))
				throw new GitPHP_DirectoryTraversalException($project);

			// allow /.git parent symlinks
			$bareOnly = GitPHP_Config::GetInstance()->GetValue('bareonly', true);
			if (!$bareOnly && substr($path, -4) == '.git') {
				$path = substr($path, 0, strlen($path)-5);
			}

			$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

			if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0))
				throw new GitPHP_ProjectrootBoundException($project);
		}

		$this->project = $project;

	}

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
		if ($this->GetConfig()->HasValue('gitphp.owner')) {
			$this->owner = $this->GetConfig()->GetValue('gitphp.owner');
		} else if ($this->GetConfig()->HasValue('gitweb.owner')) {
			$this->owner = $this->GetConfig()->GetValue('gitweb.owner');
		}

		if (empty($this->owner) && function_exists('posix_getpwuid')) {
			$uid = fileowner($this->GetPath());
			if ($uid !== false) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
				}
				$this->owner = rtrim($this->owner,',');
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

	/**
	 * Gets the project root
	 *
	 * @return string the project root
	 */
	public function GetProjectRoot()
	{
		return $this->projectRoot;
	}

	/**
	 * Gets the project description
	 *
	 * @param $trim length to trim description to (0 for no trim)
	 * @return string project description
	 */
	public function GetDescription($trim = 0)
	{

		if (!$this->readDescription) {

			$config = $this->GetConfig();

			if ($config->HasValue('gitphp.description')) {
				$this->description = $config->GetValue('gitphp.description');
			} else if ($config->HasValue('gitweb.description')) {
				$this->description = $config->GetValue('gitweb.description');
			} else if (file_exists($this->GetPath() . '/description')) {
				$this->description = file_get_contents($this->GetPath() . '/description');

				if (strpos($this->description,'Unnamed repository; edit this file') !== false)
					$this->description = '';
			} else {
				$this->description = '';
			}

			if (empty($this->description)) {

				$remotes = $this->GetRemoteHeadList()->GetOrderedHeads('-committerdate', 1);
				if (empty($remotes)) {
					//default is 'origin'
					$remote = $this->repoRemote;
				} else {
					//get first remote
					$head = reset($remotes);
					$remote = GitPHP_RemoteHeadList::GetRemoteName($head);
				}

				if ($config->HasValue('remote.'.$remote.'.url')) {
					$this->description = $config->GetValue('remote.'.$remote.'.url');
				}

				if (empty($this->description)) {
					$this->description = $this->GetCloneUrl();
				}

				if (empty($this->description)) {
					$this->description = '-';
				} else {
					// save project description if Unnamed
					$config->SetValue('gitphp.description',$this->description);
				}
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

	/**
	 * Returns whether gitdaemon is allowed for this project
	 *
	 * @return boolean git-daemon-export-ok?
	 */
	public function GetDaemonEnabled()
	{
		return file_exists($this->GetPath() . '/git-daemon-export-ok');
	}

	/**
	 * Gets the project's category
	 *
	 * @return string category
	 */
	public function GetCategory($default='')
	{
		if (!empty($this->category)) {
			return $this->category;
		}

		if ($this->GetConfig()->HasValue('gitphp.category')) {
			return $this->GetConfig()->GetValue('gitphp.category');
		}

		return $default;
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

	/**
	 * Gets the clone URL for this repository, if specified
	 *
	 * @return string clone url
	 */
	public function GetCloneUrl()
	{
		if ($this->cloneUrl !== null)
			return $this->cloneUrl;

		if ($this->GetConfig()->HasValue('gitphp.cloneurl'))
			return $this->GetConfig()->GetValue('gitphp.cloneurl');

		$cloneurl = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('cloneurl', ''), false);
		if (!empty($cloneurl))
			$cloneurl .= $this->project;

		return $cloneurl;
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

	/**
	 * Gets the push URL for this repository, if specified
	 *
	 * @return string push url
	 */
	public function GetPushUrl()
	{
		if ($this->pushUrl !== null)
			return $this->pushUrl;

		if ($this->GetConfig()->HasValue('gitphp.pushurl'))
			return $this->GetConfig()->GetValue('gitphp.pushurl');

		$pushurl = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('pushurl', ''), false);
		if (!empty($pushurl))
			$pushurl .= $this->project;

		return $pushurl;
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

	/**
	 * Gets the bug URL for this repository, if specified
	 *
	 * @return string bug url
	 */
	public function GetBugUrl()
	{
		if ($this->bugUrl != null)
			return $this->bugUrl;

		if ($this->GetConfig()->HasValue('gitphp.bugurl')) {
			return $this->GetConfig()->GetValue('gitphp.bugurl');
		}

		return GitPHP_Config::GetInstance()->GetValue('bugurl', '');
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
		if ($this->bugPattern != null)
			return $this->bugPattern;

		if ($this->GetConfig()->HasValue('gitphp.bugpattern')) {
			return $this->GetConfig()->GetValue('gitphp.bugpattern');
		}

		return GitPHP_Config::GetInstance()->GetValue('bugpattern', '');
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

		if ($this->GetConfig()->HasValue('gitphp.website')) {
			return $this->GetConfig()->GetValue('gitphp.website');
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

	/**
	 * Gets the head commit for this project
	 * Shortcut for getting the tip commit of the HEAD branch
	 *
	 * @return mixed head commit
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

		if ($this->isAndroidRepo) {
			$this->ReadHeadCommitRepo();
		} elseif ($this->GetCompat()) {
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
			/* standard pointer to head, preg to keep compat with remote heads */
			$head = preg_replace('#^refs/heads/#', '', $regs[1]);

			if ($this->GetHeadList()->Exists($head)) {
				$this->head = $this->GetHeadList()->GetHead($head)->GetHash();
			} elseif ($this->GetRemoteHeadList()->Exists($head)) {
				$this->head = $this->GetRemoteHeadList()->GetHead($head)->GetHash();
			}
		}
	}

	/**
	 * Gets the head reference for this project
	 * Only returns the raw pointer of the HEAD branch
	 *
	 * @return string head reference
	 */
	public function GetHeadReference()
	{
		if (!$this->readHeadRef)
			$this->ReadHeadCommit();

		return $this->head;
	}

	/**
	 * Read head commit for repo (no HEAD)
	 */
	private function ReadHeadCommitRepo()
	{
		$head = $this->repoRemote.'/'.$this->repoBranch;
		if ($this->GetRemoteHeadList()->Exists($head))
			$this->head = $this->GetRemoteHeadList()->GetHead($head)->GetHash();
		elseif ($this->GetRemoteHeadList()->Exists('refs/remotes/'.$head))
			$this->head = $this->GetRemoteHeadList()->GetHead('refs/remotes/'.$head)->GetHash();
	}

	/**
	 * Gets this project's epoch
	 * (time of last change)
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
	 * Reads this project's epoch
	 * (timestamp of most recent change)
	 */
	private function ReadEpoch()
	{
		$this->epochRead = true;

		if ($this->GetCompat() && !$this->isAndroidRepo) {
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

		$array = $this->GetHeadList()->GetOrderedHeads('-committerdate', 1);

		if (empty($array) && $this->showRemotes) {

			$headref = $this->repoRemote.'/'.$this->repoBranch;
			if (!$this->isAndroidRepo)
				$array = $this->GetRemoteHeadList()->GetOrderedHeads('-committerdate', 1);
			elseif ($this->GetRemoteHeadList()->Exists($headref))
				$array = array($this->GetRemoteHeadList()->GetHead($headref));
		}

		foreach ($array as $headObj) {
			$commit = $headObj->GetCommit();
			if ($commit) {
				if (intval($commit->GetCommitterEpoch()) > $epoch) {
					$epoch = (int) $commit->GetCommitterEpoch();
				}
			}
		}
		if ($epoch > 0) {
			$this->epoch = $epoch;
		}
	}

	/**
	 * Gets whether this project is running in compatibility mode
	 *
	 * @return boolean true if compatibilty mode
	 */
	public function GetCompat()
	{
		if ($this->compat !== null) {
			return $this->compat;
		}

		if ($this->GetConfig()->HasValue('gitphp.compat')) {
			return $this->GetConfig()->GetValue('gitphp.compat');
		}

		return GitPHP_Config::GetInstance()->GetValue('compat', false);
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

	/**
	 * Gets the config reader instance
	 *
	 * @return mixed config class
	 */
	public function GetConfig()
	{
		if (!$this->config) {
			$this->config = new GitPHP_GitConfig($this);
		}

		return $this->config;
	}

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

		if (is_object($hash)) {
			return null;
		}

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
		} else if (substr_compare($hash, 'refs/remotes/', 0, 13) === 0) {
			$head = substr($hash, 13);
			if ($this->GetRemoteHeadList()->Exists($head))
				return $this->GetRemoteHeadList()->GetHead($head)->GetCommit();
		}

		if ($this->GetHeadList()->Exists($hash)) {
			return $this->GetHeadList()->GetHead($hash)->GetCommit();
		}

		if ($this->GetTagList()->Exists($hash)) {
			return $this->GetTagList()->GetTag($hash)->GetCommit();
		}

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash)) {
			if (isset($this->objectManager))
				return $this->GetObjectManager()->GetCommit($hash);

			$fullHash = $this->ExpandHash($hash);
			if ($fullHash == $hash)
				throw new GitPHP_InvalidHashException($hash);
			return $this->GetCommit($fullHash);
		}

		return null;
	}

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
	 * Sets the tag list
	 *
	 * @param GitPHP_TagList $tagList tag list
	 */
	public function SetTagList($tagList)
	{
		if ($tagList && ($tagList->GetProject() !== $this))
			throw new Exception('Invalid taglist for this project');

		$this->tagList = $tagList;
	}

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
	 * Sets the head list
	 *
	 * @param GitPHP_HeadList $headList head list
	 */
	public function SetHeadList($headList)
	{
		if ($headList && ($headList->GetProject() !== $this))
			throw new Exception('Invalid headlist for this project');

		$this->headList = $headList;
	}

	/**
	 * Gets the remotes head list
	 * @author tpruvot
	 *
	 * @return GitPHP_HeadList head list
	 */
	public function GetRemoteHeadList()
	{
		if (!$this->remoteHeadList) {
			$this->remoteHeadList = new GitPHP_RemoteHeadList($this);
			$this->remoteHeadList->SetCompat($this->GetCompat());
		}
		return $this->remoteHeadList;
	}

	/**
	 * Sets the remotes head list
	 * @author tpruvot
	 *
	 * @param GitPHP_RemoteHeadList $headList head list
	 */
	public function SetRemoteHeadList($headList)
	{
		if ($remoteHeadList && ($remoteHeadList->GetProject() !== $this))
			throw new Exception('Invalid remote headlist for this project');

		$this->remoteHeadList = $headList;
	}

	/**
	 * Get the git object manager for this project
	 *
	 * @return GitPHP_GitObjectManager
	 */
	public function GetObjectManager()
	{
		return $this->objectManager;
	}

	/**
	 * Set the git object manager for this project
	 *
	 * @param GitPHP_GitObjectManager $objectManager object manager
	 */
	public function SetObjectManager($objectManager)
	{
		if ($objectManager && ($objectManager->GetProject() !== $this))
			throw new Exception('Invalid object manager for this project');

		$this->objectManager = $objectManager;
	}

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
	 * Reads the list of refs for this project
	 */
	protected function ReadRefList()
	{
		$this->GetHeadList();
		if ($this->showRemotes)
			$this->GetRemoteHeadList();
		$this->readRefs = true;
	}

	/**
	 * Gets a single tag
	 *
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
				$tagObj = $this->GetObjectManager()->GetTag($tag, $hash);
			}

			$memoryCache->Set($key, $tagObj);
		}

		return $tagObj;
	}

	/**
	 * list of remote branches for the project
	 *
	 * @param integer $count number of heads to load
	 * @return array array of heads
	 */
	public function GetRemotes($count = 0)
	{
		if (!$this->showRemotes)
			return null;

		return $this->GetRemoteHeadList()->GetOrderedHeads('-committerdate', $count);
	}

	/**
	 * Gets list of heads for this project by age descending
	 * @deprecated
	 *
	 * @param integer $count number of heads to load
	 * @return array array of heads
	 */
	public function GetHeads($count = 0)
	{
		$this->GetHeadList()->GetOrderedHeads('-committerdate', $count);
	}

	/**
	 * Gets a single head
	 *
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

	/**
	 * SetLogGlyph
	 *
	 * Add a basic symbol for short log list, to build a basic "bloc-graph"
	 * Allow to know if the commits are "forward" or from another (merged) branch.
	 *
	 * '|' normal commit (follow previous)
	 * '>' for merges (2 parents)
	 * 'o' for the last of a bloc (doesn't follow previous commit in table)
	 */
	private function SetLogGlyph($log)
	{
		$len = count($log);
		for ($i = 0; $i < $len; ++$i) {
			$log[$i]->glyph = 'o';
			$log[$i]->glyphClass = 'clast';
			if ($i > 0) {
				$hash = $log[$i]->GetHash();
				// use end() to check the unique element of $parents array
				if (count($parents) == 1 && $hash == end($parents)->GetHash()) {
					$log[$i-1]->glyph = '|';
					$log[$i-1]->glyphClass = 'cinside';
				}
			}
			$parents = $log[$i]->GetParents();
			if (count($parents) == 2) {
				$log[$i]->glyph = '>';
				$log[$i]->glyphClass = 'cmerge';
			}
		}
	}

	/**
	 * Gets log entries as an array of hashes
	 *
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of hashes
	 */
	private function GetLogHash($hash, $count = 50, $skip = 0)
	{
		return $this->RevList($hash, $count, $skip);
	}

	/**
	 * Gets log entries as an array of commit objects
	 *
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of commit objects
	 */
	public function GetLog($hash, $count = 50, $skip = 0)
	{
		if ($this->GetCompat() || ($skip > GitPHP_Config::GetInstance()->GetValue('largeskip', 200)) ) {
			$log = $this->GetLogGit($hash, $count, $skip);
		} else {
			$log = $this->GetLogRaw($hash, $count, $skip);
		}
		$this->SetLogGlyph($log);
		return $log;
	}

	/**
	 * Gets log entries using git exe
	 *
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of commit objects
	 */
	private function GetLogGit($hash, $count = 50, $skip = 0)
	{
		$log = $this->GetLogHash($hash, $count, $skip);
		$len = count($log);
		for ($i = 0; $i < $len; ++$i) {
			$log[$i] = $this->GetCommit($log[$i]);
		}
		return $log;
	}

	/**
	 * Gets log entries using raw git objects
	 * Based on history walking code from glip
	 */
	private function GetLogRaw($hash, $count = 50, $skip = 0)
	{
		$total = $count + $skip;

		$inc = array();
		$num = 0;
		$queue = array($this->GetCommit($hash));
		while (($commit = array_shift($queue)) !== null) {
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (!isset($inc[$parent->GetHash()])) {
					$inc[$parent->GetHash()] = 1;
					$queue[] = $parent;
					$num++;
				} else {
					$inc[$parent->GetHash()]++;
				}
			}
			if ($num >= $total)
				break;
		}

		$queue = array($this->GetCommit($hash));
		$log = array();
		$num = 0;
		while (($commit = array_pop($queue)) !== null) {
			array_push($log, $commit);
			$num++;
			if ($num == $total) {
				break;
			}
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (isset($inc[$parent->GetHash()])) {
					if (--$inc[$parent->GetHash()] == 0) {
						$queue[] = $parent;
					}
				}
			}
		}

		if ($skip > 0) {
			$log = array_slice($log, $skip, $count);
		}
		usort($log, array('GitPHP_Commit', 'CompareAge'));
		return $log;
	}

	/**
	 * Gets a blob from this project
	 *
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

			$memoryCache->Set($key, $blob);
		}

		return $blob;
	}

	/**
	 * Gets a tree from this project
	 *
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

			$memoryCache->Set($key, $tree);
		}

		return $tree;
	}

	/**
	 * Gets the git object loader for this project
	 */
	public function GetObjectLoader()
	{
		if (!$this->objectLoader) {
			$this->objectLoader = new GitPHP_GitObjectLoader($this);
		}

		return $this->objectLoader;
	}


	/**
	 * Sets the git object loader for this project
	 *
	 * @param GitPHP_GitObjectLoader $objectLoader object loader
	 */
	public function SetObjectLoader($objectLoader)
	{
		if ($objectLoader && ($objectLoader->GetProject() !== $this))
			throw new Exception('Invalid object loader for this project');

		$this->objectLoader = $objectLoader;
	}

	/**
	 * Gets the raw content of an object with type check
	 *
	 * @param string $hash object hash
	 * @param integer type GitPHP_Pack object type constant
	 * @return string object data
	 */
	public function GetObjectByType($hash, $type = 0)
	{
		$loader = $this->GetObjectLoader();
		if (!is_object($loader))
			throw new GitPHP_MessageException(sprintf('Unable to get object loader on project %1$s', $this->project), true);

		return $loader->GetObject($hash, $type);
	}

	/**
	 * Read the list of packs in the repository
	 */
	private function ReadPacks()
	{
		$dh = opendir($this->GetPath() . '/objects/pack');
		if ($dh === false) {
			return;
		}
		while (($file = readdir($dh)) !== false) {
			if (preg_match('/^pack-([0-9A-Fa-f]{40})\.pack$/', $file, $regs)) {
				try {
					$this->packs[] = new GitPHP_Pack($this, $regs[1]);
				} catch (Exception $e) {
					GitPHP_Log::GetInstance()->Log($this->project.': error in '.$file);
				}
			}
		}
		$this->packsRead = true;
	}

	/**
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

		$projAbbrevLen = $this->GetAbbreviateLength();
		if ($projAbbrevLen > 0) {
			$abbrevLen = max(7, min($projAbbrevLen, 40));
			$args[] = '--abbrev='.$abbrevLen;
		}

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
		$abbrevLen = self::ABBREV_HASH_MIN;

		if ($this->GetConfig()->HasValue('core.abbrev')) {
			$abbrevLen = max(4, min($this->GetConfig()->GetValue('core.abbrev'), 40));
		}

		$prefix = substr($hash, 0, $abbrevLen);

		if (!GitPHP_Config::GetInstance()->GetValue('uniqueabbrev', false)) {
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

	/**
	 * Expands a hash using raw git objects
	 *
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	private function ExpandHashRaw($abbrevHash)
	{
		$matches = $this->FindHashObjects($abbrevHash);
		if (count($matches) > 0) {
			return $matches[0];
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($abbrevHash);
			if (count($matches) > 0) {
				return $matches[0];
			}
		}

		return $abbrevHash;
	}

	/**
	 * Finds loose hash files matching a given prefix
	 *
	 * @param string $prefix hash prefix
	 * @return array array of hash objects
	 */
	private function FindHashObjects($prefix)
	{
		$matches = array();
		if (empty($prefix)) {
			return $matches;
		}

		$subdir = substr($prefix, 0, 2);
		$fulldir = $this->GetPath() . '/objects/' . $subdir;
		if (!is_dir($fulldir.'/.')) {
			return $matches;
		}

		$prefixlen = strlen($prefix);
		$dh = opendir($fulldir);
		if ($dh !== false) {
			while (($file = readdir($dh)) !== false) {
				$fullhash = $subdir . $file;
				if (substr_compare($fullhash, $prefix, 0, $prefixlen) === 0) {
					$matches[] = $fullhash;
				}
			}
		}
		return $matches;
	}

	/**
	 * Gets a list of commits with commit messages matching the given pattern
	 *
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchCommit($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		if (GitPHP_GitExe::GetInstance()->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';

		// if search for hash, dont use grep
		if (preg_match('/^([0-9a-f]{40})$/i', $pattern, $regs))
			$count = 1;
		else
			$args[] = '--grep="' . addslashes($pattern) . '"';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

	/**
	 * Gets a list of commits with authors matching the given pattern
	 *
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchAuthor($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		if (GitPHP_GitExe::GetInstance()->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';

		$args[] = '--author="' . addslashes($pattern) . '"';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

	/**
	 * Gets a list of commits with committers matching the given pattern
	 *
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchCommitter($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		if (GitPHP_GitExe::GetInstance()->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';

		$args[] = '--committer="' . addslashes($pattern) . '"';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

	/**
	 * Common code for using rev-list command
	 *
	 * @param string $hash hash to list from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @param array $args args to give to rev-list
	 * @return array array of hashes
	 */
	private function RevList($hash, $count = 50, $skip = 0, $args = array())
	{
		if ($count < 1)
			return;

		$canSkip = true;
		
		if ($skip > 0)
			$canSkip = GitPHP_GitExe::GetInstance()->CanSkip();

		if ($canSkip) {
			$args[] = '--max-count=' . $count;
			if ($skip > 0) {
				$args[] = '--skip=' . $skip;
			}
		} else {
			$args[] = '--max-count=' . ($count + $skip);
		}

		$args[] = $hash;

		$revlist = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_REV_LIST, $args));

		if (!$revlist[count($revlist)-1]) {
			/* the last newline creates a null entry */
			array_splice($revlist, -1, 1);
		}

		if (($skip > 0) && (!$canSkip)) {
			return array_slice($revlist, $skip, $count);
		}

		return $revlist;
	}

	/**
	 * Compares two projects by project name
	 *
	 * @return integer age
	 */
	public function GetAge()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		if ($this->epoch == 0)
			return -1;

		return time() - $this->epoch;
	}

	/**
	 * Reads this project's epoch
	 * (timestamp of most recent change)
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareProject($a, $b)
	{
		$catCmp = GitPHP_Project::CompareCategory($a, $b);
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetProject(), $b->GetProject());
	}

	/**
	 * Compares two projects by description
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareDescription($a, $b)
	{
		// disable category display on this column sort
		$b->SetCategory('');

		return strcmp($a->GetDescription(), $b->GetDescription());
	}

	/**
	 * Compares two projects by owner
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareOwner($a, $b)
	{
		$catCmp = GitPHP_Project::CompareCategory($a, $b);
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetOwner(), $b->GetOwner());
	}

	/**
	 * Compares two projects by age
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$catCmp = GitPHP_Project::CompareCategoryAge($a, $b);
		if ($catCmp !== 0)
			return $catCmp;

		if ($a->GetAge() === $b->GetAge())
			return 0;
		return ($a->GetAge() < $b->GetAge() ? -1 : 1);
	}

	/**
	 * Compares two projects by category
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareCategory($a, $b)
	{
		if ($b->GetCategory() === '' && $a->GetCategory() )
			return 1;
		if ($a->GetCategory() === '' && $b->GetCategory() )
			return -1;
		return strcasecmp($a->GetCategory(''), $b->GetCategory(''));
	}

	/**
	 * Compares two projects by category age
	 *
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareCategoryAge($a, $b)
	{
		if ($a->categoryAge === $b->categoryAge)
			return 0;
		return ($a->categoryAge < $b->categoryAge ? -1 : 1);
	}

}
