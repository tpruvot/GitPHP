<?php
/**
 * Class for a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\Project
 */
class GitPHP_Project
{

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

	/**
	 * The category
	 *
	 * @var string
	 */
	protected $category = '';

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

	/**
	 * Users allowed access to this project
	 *
	 * @var string[]|null
	 */
	protected $allowedUsers = null;

	/**
	 * The git object manager
	 *
	 * @var GitPHP_GitObjectManager
	 */
	protected $objectManager;

	/**
	 * Data load strategy
	 *
	 * @var GitPHP_ProjectLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Class constructor
	 *
	 * @param string $projectRoot project root
	 * @param string $project project
	 * @param GitPHP_ProjectLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($projectRoot, $project, GitPHP_ProjectLoadStrategy_Interface $strategy = null)
	{
		$this->projectRoot = GitPHP_Util::AddSlash($projectRoot);
		$this->SetProject($project);

		if ($strategy)
			$this->SetStrategy($strategy);
	}

	/**
	 * Gets the project
	 *
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
			throw new GitPHP_InvalidDirectoryException($fullPath);
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new GitPHP_InvalidGitRepositoryException($project);
		}

		if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project)) {
			throw new GitPHP_DirectoryTraversalException($project);
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0)) {
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
			if (file_exists($this->GetPath() . '/description')) {
				$this->description = trim(file_get_contents($this->GetPath() . '/description'));
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

	/**
	 * Gets the allowed users for this project
	 *
	 * @return string[]|null
	 */
	public function GetAllowedUsers()
	{
		return $this->allowedUsers;
	}

	/**
	 * Sets the allowed users for this project
	 *
	 * @param string[]|null $allowedUsers allowed users
	 */
	public function SetAllowedUsers($allowedUsers)
	{
		$this->allowedUsers = $allowedUsers;
	}

	/**
	 * Checks if a user has access to this project
	 *
	 * @param string $username username
	 * @return boolean whether user is allowed
	 */
	public function UserCanAccess($username)
	{
		if (empty($this->allowedUsers))
			return true;

		if (empty($username))
			return false;

		return in_array($username, $this->allowedUsers);
	}

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
	 * Gets the head reference for this project
	 *
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
	 * Reads the head commit hash
	 */
	public function ReadHeadCommit()
	{
		$this->readHeadRef = true;

		$this->head = $this->strategy->LoadHead($this);
	}

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

		$this->epoch = $this->strategy->LoadEpoch($this);
	}

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
	}

	/**
	 * Set data load strategy
	 *
	 * @param GitPHP_ProjectLoadStrategy_Interface $strategy strategy
	 */
	public function SetStrategy(GitPHP_ProjectLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
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

		if ($hash === 'HEAD')
			return $this->GetHeadCommit();

		if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
			return $this->GetObjectManager()->GetCommit($hash);
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
			return $this->GetObjectManager()->GetCommit($hash);
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
	 * Calculates the unique abbreviated hash for a full hash
	 *
	 * @param string $hash full hash
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($hash)
	{
		return $this->strategy->AbbreviateHash($this, $hash);
	}

	/**
	 * Finds the full hash for an abbreviated hash
	 *
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($abbrevHash)
	{
		return $this->strategy->ExpandHash($this, $abbrevHash);
	}

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

}
