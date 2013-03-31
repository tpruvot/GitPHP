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
	 */
	protected $readHeadRef = false;

	/**
	 * Stores the tags for the project
	 */
	protected $tags = array();

	/**
	 * Stores the heads for the project
	 */
	protected $heads = array();

	/**
	 * Stores the remotes for the project
	 */
	protected $remotes = array();

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
	 * Stores the list of packs
	 */
	protected $packs = array();

	/**
	 * Stores whether packs have been read
	 */
	protected $packsRead = false;

	/**
	 * Stores whether this project is running
	 * in compatibility mode
	 */
	protected $compat = null;

	/**
	 * Stores the config reader internally
	 */
	protected $config = null;

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
			throw new Exception(sprintf(__('%1$s is not a directory'), $project));
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception(sprintf(__('%1$s is not a git repository'), $project));
		}

		if (GitPHP_Config::GetInstance()->GetValue('projectroot') != '/') {

			if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project))
			throw new Exception(sprintf(__('%1$s is attempting directory traversal'), $project));

			// allow /.git parent symlinks
			$bareOnly = GitPHP_Config::GetInstance()->GetValue('bareonly', true);
			if (!$bareOnly && substr($path, -4) == '.git') {
				$path = substr($path, 0, strlen($path)-5);
			}

			$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

			if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0))
			throw new Exception(sprintf(__('%1$s is outside of the projectroot'), $path));
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

				if (empty($this->remotes)) {
					//default is 'origin'
					$remote = $this->repoRemote;
				} else {
					//get first remote
					$rm = reset($this->remotes);
					$remote = $rm->GetRemoteName();
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
			/* standard pointer to head */
			if (!$this->readRefs)
				$this->ReadRefList();

			$head = $regs[1];
			if (!isset($this->heads[$head])) {
				$head = preg_replace('#^refs/heads/#', '', $regs[1]);
				GitPHP_Log::GetInstance()->Log($this->project.': [ReadHeadCommitRaw] '.$regs[1].' => '.$head);
			}

			if (isset($this->heads[$head])) {
				$this->head = $this->heads[$head];
			} elseif (isset($this->remotes[$head])) {
				$this->head = $this->remotes[$head]->GetHash();
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
		/* standard pointer to head */
		if (!$this->readRefs)
			$this->ReadRefList();

		$head = 'refs/remotes/'.$this->repoRemote.'/'.$this->repoBranch;
		if (is_object($this->remotes[$head]) && empty($this->head)) {
			$this->head = $this->remotes[$head]->GetHash();
		}
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
		if (!$this->readRefs)
			$this->ReadRefList();

		$epoch = 0;
		$isRemote = false;

		$array = $this->heads;
		if (empty($array) && $this->showRemotes) {
			$array = $this->remotes;
			//only use selected branch if set, faster
			$selected = 'refs/remotes/'.$this->repoRemote.'/'.$this->repoBranch;
			if (array_key_exists($selected, $this->remotes)) {
				$array = array($selected => $this->remotes[$selected]->GetHash());
				$isRemote = true;
			}
		}

		foreach ($array as $head => $hash) {
			if (!$isRemote) {
				$headObj = $this->GetHead($head);
				$commit = $headObj->GetCommit();
			} else {
				$rhObj = $this->remotes[$selected];
				$commit = $rhObj->GetCommit();
			}
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
			$head = $this->GetHead(substr($hash, 11));
			if ($head != null)
				return $head->GetCommit();
			return null;
		} else if (substr_compare($hash, 'refs/tags/', 0, 10) === 0) {
			$tag = $this->GetTag(substr($hash, 10));
			if ($tag != null) {
				$obj = $tag->GetCommit();
				if ($obj != null) {
					return $obj;
				}
			}
			return null;
		} else if (substr_compare($hash, 'refs/remotes/', 0, 13) === 0) {
			if (!array_key_exists($hash, $this->remotes)) {
				$this->remotes[$hash] = new GitPHP_RemoteHead($this, $hash);
			}
			return $this->remotes[$hash]->GetCommit();
		}

		if (!$this->readRefs)
			$this->ReadRefList();

		if (isset($this->heads[$hash])) {
			$headObj = $this->GetHead($hash);
			return $headObj->GetCommit();
		}

		if (isset($this->tags[$hash])) {
			$tagObj = $this->GetTag($hash);
			return $tagObj->GetCommit();
		}

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash)) {
			return $this->GetCommit($this->ExpandHash($hash));
		}

		return null;
	}

	/**
	 * Gets the list of refs for the project
	 *
	 * @param string $type type of refs to get
	 * @return array array of refs
	 */
	public function GetRefs($type = '')
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		$tags = array();
		if ($type !== 'heads') {
			foreach ($this->tags as $tag => $hash) {
				$tags['refs/tags/' . $tag] = $this->GetTag($tag);
			}
			if ($type == 'tags')
				return $tags;
		}

		if ($type == 'remotes') {
			// return only remote heads for badges "github/ics" => "32abdc..."
			$heads = array();
			foreach ($this->remotes as $head => $rh) {
				$heads[$rh->GetName()] = $rh->GetHash();
			}
			return $heads;
		}

		$heads = array();
		if ($type !== 'tags') {
			foreach ($this->heads as $head => $hash) {
				$heads['refs/heads/' . $head] = $this->GetHead($head);
			}
			if ($type == 'heads')
				return $heads;

			foreach ($this->remotes as $head => $rh) {
				$heads[$rh->GetName()] = $rh->GetHash();
			}

		}

		return array_merge($heads, $tags);
	}

	/**
	 * Reads the list of refs for this project
	 */
	protected function ReadRefList()
	{
		$this->readRefs = true;

		if ($this->GetCompat() && !$this->isAndroidRepo) {
			$this->ReadRefListGit();
		} else {
			$this->ReadRefListRaw();
		}
	}

	/**
	 * Reads the list of refs for this project using the git executable
	 */
	private function ReadRefListGit()
	{
		$args = array();
		if (!$this->showRemotes) {
			$args[] = '--heads';
			$args[] = '--tags';
		}
		$args[] = '--dereference';
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_SHOW_REF, $args);

		$lines = explode("\n", $ret);

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/(tags|heads|remotes)\/([^^]+)(\^{})?$/', $line, $regs)) {
				try {
					$key = 'refs/' . $regs[2] . '/' . $regs[3];
					if ($regs[2] == 'tags') {
						if ((!empty($regs[4])) && ($regs[4] == '^{}')) {
							if (isset($this->tags[$regs[3]])) {
								$tagObj = $this->GetTag($regs[3]);
								$tagObj->SetCommitHash($regs[1]);
								unset($tagObj);
							}
								
						} else if (!isset($this->tags[$regs[3]])) {
							$this->tags[$regs[3]] = $regs[1];
						}
					} else if ($regs[2] == 'heads') {

						$this->heads[$regs[3]] = $regs[1];

					} else if ($this->showRemotes && $regs[2] == 'remotes') {
						// Todo: convert to ref => hash
						// $this->remotes[$regs[3]] = $regs[1];
						if (!isset($this->remotes[$key]) && substr($key, -5) != '/HEAD')
							$this->remotes[$key] = new GitPHP_RemoteHead($this, $regs[3], $regs[1]);
					}
				} catch (Exception $e) {
				}
			}
		}
	}

	/**
	 * Reads the list of refs for this project using the raw git files
	 */
	private function ReadRefListRaw()
	{
		$path = $this->GetPath();
		$pathlen = strlen($path) + 1;

		// read loose heads
		$heads = GitPHP_Util::ListDir($path . '/refs/heads');
		for ($i = 0; $i < count($heads); $i++) {
			$key = trim(substr($heads[$i], $pathlen), "/\\");
			$head = substr($key, strlen('refs/heads/'));

			if (isset($this->heads[$head])) {
				continue;
			}

			$hash = trim(file_get_contents($heads[$i]));
			if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
				$this->heads[$head] = $hash;
			}
		}

		// read loose tags
		$tags = GitPHP_Util::ListDir($path . '/refs/tags');
		for ($i = 0; $i < count($tags); $i++) {
			$key = trim(substr($tags[$i], $pathlen), "/\\");
			$tag = substr($key, strlen('refs/tags/'));

			if (isset($this->tags[$tag])) {
				continue;
			}

			$hash = trim(file_get_contents($tags[$i]));
			if (preg_match('/^[0-9a-f]{40}$/i', $hash)) {
				$tag = substr($key, strlen('refs/tags/'));
				$this->tags[$tag] = $hash;
			}
		}

		// check packed refs (only updated on git gc !)
		if (file_exists($path . '/packed-refs')) {
			$packedRefs = explode("\n", file_get_contents($path . '/packed-refs'));

			$lastTag = null;
			foreach ($packedRefs as $ref) {

				if (preg_match('/^\^([0-9a-f]{40})$/i', $ref, $regs)) {
					// dereference of previous ref
					if (!empty($lastTag)) {
						$tagObj = $this->GetTag($lastTag);
						$tagObj->SetCommitHash($regs[1]);
						unset($tagObj);
					}
				}

				$lastTag = null;

				if (preg_match('/^([0-9A-Fa-f]{40}) refs\/(tags|heads|remotes)\/(.+)$/', $ref, $regs)) {
					// standard tag/head
					$key = 'refs/' . $regs[2] . '/' . $regs[3];
					if ($regs[2] == 'tags') {
						if (!isset($this->tags[$regs[3]])) {
							$this->tags[$regs[3]] = $regs[1];
							$lastTag = $regs[3];
						}
					} else if ($regs[2] == 'heads') {
						if (!isset($this->heads[$regs[3]])) {
							$this->heads[$regs[3]] = $regs[1];
						}
					} else if ($this->showRemotes && $regs[2] == 'remotes') {
						if (!isset($this->remotes[$key])) {
							$this->remotes[$key] = new GitPHP_RemoteHead($this, $regs[3], $regs[1]);
						}
					}
				}
			}
		}

		// double check the remote heads refs
		if ($this->showRemotes ) {

			if (count($this->heads) == 1) {
				// set branch as default HEAD, if alone
				$this->head = reset($this->heads);
				GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] use '.$this->head.' as default HEAD');
			}

			$tag = null;
			$heads = GitPHP_Util::ListDir($path . '/refs/remotes');
			for ($i = 0; $i < count($heads); $i++) {

				//sample 'gingerbread' content in 'm' folder:
				//  ref: refs/remotes/github/gingerbread

				$head = trim(file_get_contents($heads[$i]));
				if (preg_match('/^ref: (.+)$/', $head, $regs)) {
					$heads[$i] = $path . "/". $regs[1];
				}

				$key = trim(substr($heads[$i], $pathlen), "/\\");

				// force double checking only on current repo branch
				if (isset($this->remotes[$key]) && $key != 'refs/remotes/'.$this->repoRemote.'/'.$this->repoBranch) {
					continue;
				}

				GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] remote='.$key);

				$m = $heads[$i];
				if (!is_file($m)) {
					//replace remote name by "m", look like the current remote
					$m = preg_replace('#(refs/remotes/)([^\/]+)#','$1m',$m);
					//GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] replace '.$heads[$i].' by '.$m);
				}

				if (is_file($m)) {
					$hash = trim(file_get_contents($m));
					if (preg_match('/^[0-9a-f]{40}$/i', $hash)) {
						$head = $key;
						$this->remotes[$key] = new GitPHP_RemoteHead($this, $head, $hash);
						GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] found head='.$head.'=>'.$hash);

					} elseif (preg_match('#^refs/tags/#', $hash)) {
						GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] found a tag '.$hash.' as remote');
						$tagObj = $this->GetTag(preg_replace('#^refs/tags/#','',$hash));
						if (is_object($tagObj)) {
							$head = $key;
							$hash = $tagObj->GetCommit()->GetHash();
							$this->remotes[$key] = new GitPHP_RemoteHead($this, $head, $hash);
						}
					}

					// if the remote hash is not found, add it as-is ...
					$head = $key;
					if (!array_key_exists($key, $this->remotes)) {
						$this->remotes[$key] = new GitPHP_RemoteHead($this, $head);
						GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] found head='.$head);
					}

				} else {
					GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] '.$key.' not found');
				}
			}

			//use defaut branch set in manifest, often missing
			$default = $this->repoRemote.'/'.$this->repoBranch;
			$key = 'refs/remotes/'.$default;
			if (!array_key_exists($key,$this->remotes)) {
				GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] add missing remote branch '.$key.'');

				$this->remotes[$key] = new GitPHP_RemoteHead($this, $default);
			} elseif (!isset($this->head)) {
				// set repo head, with default hash
				$this->head = $this->remotes[$key]->GetHash();
			}

			GitPHP_Log::GetInstance()->Log($this->project.': [ReadRefListRaw] found '.count($this->remotes).' remote branches');
		}
	}

	/**
	 * Gets list of tags for this project by age descending
	 *
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	public function GetTags($count = 0)
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		if ($this->GetCompat()) {
			return $this->GetTagsGit($count);
		} else {
			return $this->GetTagsRaw($count);
		}
	}

	/**
	 * Gets list of tags for this project by age descending using git executable
	 *
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	private function GetTagsGit($count = 0)
	{
		$args = array();
		$args[] = '--sort=-creatordate';
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			$args[] = '--count=' . $count;
		}
		$args[] = '--';
		$args[] = 'refs/tags';
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_FOR_EACH_REF, $args);

		$lines = explode("\n", $ret);

		$tags = array();

		foreach ($lines as $ref) {
			$tag = substr($ref, strlen('refs/tags/'));
			if (isset($this->tags[$tag])) {
				$tags[] = $this->GetTag($tag);
			}
		}

		return $tags;
	}

	/**
	 * Gets list of tags for this project by age descending using raw git objects
	 *
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	private function GetTagsRaw($count = 0)
	{
		$tags = array();
		foreach ($this->tags as $tag => $hash) {
			$tags[] = $this->GetTag($tag);
		}
		usort($tags, array('GitPHP_Tag', 'CompareCreationEpoch'));

		if (($count > 0) && (count($tags) > $count)) {
			$tags = array_slice($tags, 0, $count);
		}

		return $tags;
	}

	/**
	 * Gets a single tag
	 *
	 * @param string $tag tag to find
	 * @return mixed tag object
	 */
	public function GetTag($tag)
	{
		if (empty($tag))
			return null;

		$key = GitPHP_Tag::CacheKey($this->project, $tag);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$tagObj = $memoryCache->Get($key);

		if (!$tagObj) {
			$tagObj = GitPHP_Cache::GetObjectCacheInstance()->Get($key);

			if (!$tagObj) {
				if (!$this->readRefs)
					$this->ReadRefList();

				$hash = '';
				if (isset($this->tags[$tag]))
					$hash = $this->tags[$tag];

				$tagObj = new GitPHP_Tag($this, $tag, $hash);
			}

			$memoryCache->Set($key, $tagObj);
		}

		return $tagObj;
	}

	/**
	 * list of remote branches for the project
	 *
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	public function GetRemotes($count = 0)
	{
		if (!$this->showRemotes)
			return null;

		if (!$this->readRefs)
			$this->ReadRefList();

		if (GitPHP_Config::GetInstance()->GetValue('compat', false)) {
			return $this->GetRemotesGit($count);
		} else {
			return $this->GetRemotesRaw($count);
		}
	}

	/**
	 * Gets the list of sorted remote heads using the git executable
	 *
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	private function GetRemotesGit($count = 0)
	{
		$args = array();
		$args[] = '-r';
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), 'branch', $args);

		$lines = explode("\n", $ret);

		$remotes = array();
		foreach ($lines as $ref) {
			$key = 'refs/remotes/'.trim($ref);
			if (!isset($this->remotes[$key])) {
				$this->remotes[$key] = new GitPHP_RemoteHead($this, $key);
			}
			$remotes[$key] = $this->remotes[$key];
		}
		//to fix...
		//usort($remotes, array('GitPHP_RemoteHead', 'CompareAge'));
		if (($count > 0) && (count($remotes) > $count)) {
			$remotes = array_slice($this->remotes, 0, $count);
		}
		return $remotes;
	}

	/**
	 * Gets the list of sorted remote heads using raw git objects
	 *
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	private function GetRemotesRaw($count = 0)
	{
		$heads = $this->remotes;
		usort($heads, array('GitPHP_RemoteHead', 'CompareAge'));

		if (($count > 0) && (count($heads) > $count)) {
			$heads = array_slice($heads, 0, $count);
		}
		return $heads;
	}

	/**
	 * Gets list of heads for this project by age descending
	 *
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	public function GetHeads($count = 0)
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		if ($this->GetCompat()) {
			return $this->GetHeadsGit($count);
		} else {
			return $this->GetHeadsRaw($count);
		}
	}

	/**
	 * Gets the list of sorted heads using the git executable
	 *
	 * @param integer $count number of heads to load
	 * @return array array of heads
	 */
	private function GetHeadsGit($count = 0)
	{
		$args = array();
		$args[] = '--sort=-committerdate';
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			$args[] = '--count=' . $count;
		}
		$args[] = '--';
		$args[] = 'refs/heads';
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetPath(), GIT_FOR_EACH_REF, $args);

		$lines = explode("\n", $ret);

		$heads = array();

		foreach ($lines as $ref) {
			$head = substr($ref, strlen('refs/heads/'));
			if (isset($this->heads[$head])) {
				$heads[] = $this->GetHead($head);
			}
		}

		return $heads;
	}

	/**
	 * Gets the list of sorted heads using raw git objects
	 *
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	private function GetHeadsRaw($count = 0)
	{
		$heads = array();
		foreach ($this->heads as $head => $hash) {
			$heads[] = $this->GetHead($head);
		}
		usort($heads, array('GitPHP_Head', 'CompareAge'));

		if (($count > 0) && (count($heads) > $count)) {
			$heads = array_slice($heads, 0, $count);
		}
		return $heads;
	}

	/**
	 * Gets a single head
	 *
	 * @param string $head head to find
	 * @return mixed head object
	 */
	public function GetHead($head)
	{
		if (empty($head))
			return null;

		$key = GitPHP_Head::CacheKey($this->project, $head);
		$memoryCache = GitPHP_MemoryCache::GetInstance();
		$headObj = $memoryCache->Get($key);

		if (!$headObj) {
			if (!$this->readRefs)
				$this->ReadRefList();

			$hash = '';
			if (isset($this->heads[$head]))
				$hash = $this->heads[$head];

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
	 * Gets the raw content of an object
	 *
	 * @param string $hash object hash
	 * @return string object data
	 */
	public function GetObject($hash, &$type = 0)
	{
		if (!preg_match('/^[0-9a-f]{40}$/i', $hash)) {
			return false;
		}

		// first check if it's unpacked
		$path = $this->GetPath() . '/objects/' . substr($hash, 0, 2) . '/' . substr($hash, 2);
		if (file_exists($path)) {
			list($header, $data) = explode("\0", gzuncompress(file_get_contents($path)), 2);
			sscanf($header, "%s %d", $typestr, $size);
			switch ($typestr) {
				case 'commit':
					$type = GitPHP_Pack::OBJ_COMMIT;
					break;
				case 'tree':
					$type = GitPHP_Pack::OBJ_TREE;
					break;
				case 'blob':
					$type = GitPHP_Pack::OBJ_BLOB;
					break;
				case 'tag':
					$type = GitPHP_Pack::OBJ_TAG;
					break;
			}
			return $data;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		// then try packs
		foreach ($this->packs as $pack) {
			$data = $pack->GetObject($hash, $type);
			if ($data !== false) {
				return $data;
			}
		}

		return false;
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
			if (preg_match('/^pack-([0-9A-Fa-f]{40})\.idx$/', $file, $regs)) {
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

		$hashMap = array();

		$matches = $this->FindHashObjects($prefix);
		foreach ($matches as $matchingHash) {
			$hashMap[$matchingHash] = 1;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($prefix);
			foreach ($matches as $matchingHash) {
				$hashMap[$matchingHash] = 1;
			}
		}

		if (count($hashMap) == 0) {
			return $hash;
		}

		if (count($hashMap) == 1) {
			return $prefix;
		}

		for ($len = $abbrevLen+1; $len < 40; $len++) {
			$prefix = substr($hash, 0, $len);

			foreach ($hashMap as $matchingHash => $val) {
				if (substr_compare($matchingHash, $prefix, 0, $len) !== 0) {
					unset($hashMap[$matchingHash]);
				}
			}

			if (count($hashMap) == 1) {
				return $prefix;
			}
		}

		return $hash;
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
			return $this->ExpandHashRaw($abbrevHash);
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
