<?php
/**
 * GitPHP Tag
 *
 * Represents a single tag object
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tag extends GitPHP_Ref implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
{
	
	/**
	 * Indicates whether data for this tag has been read
	 */
	protected $dataRead = false;

	/**
	 * Stores the object internally
	 */
	protected $object;

	/**
	 * Stores the commit hash internally
	 */
	protected $commitHash;

	/**
	 * Stores the type internally
	 */
	protected $type;

	/**
	 * Stores the tagger internally
	 */
	protected $tagger;

	/**
	 * Stores the tagger epoch internally
	 */
	protected $taggerEpoch;

	/**
	 * Stores the tagger timezone internally
	 */
	protected $taggerTimezone;

	/**
	 * Stores the tag comment internally
	 */
	protected $comment = array();

	/**
	 * Observers
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Data load strategy
	 *
	 * @var GitPHP_TagLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Instantiates tag
	 *
	 * @param mixed $project the project
	 * @param string $tag tag name
	 * @param GitPHP_TagLoadStrategy_Interface $strategy load strategy
	 * @param string $tagHash tag hash
	 * @throws Exception exception on invalid tag or hash
	 */
	public function __construct($project, $tag, GitPHP_TagLoadStrategy_Interface $strategy, $tagHash = '')
	{
		parent::__construct($project, 'tags', $tag, $tagHash);

		if (!$strategy)
			throw new Exception('Tag load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_TagLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_TagLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the object this tag points to
	 *
	 * @return mixed object for this tag
	 */
	public function GetObject()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($this->type == 'commit') {
			return $this->GetProject()->GetCommit($this->object);
		} else if ($this->type == 'tag') {
			return $this->GetProject()->GetTagList()->GetTag($this->object);
		} else if ($this->type == 'blob') {
			return $this->GetProject()->GetBlob($this->object);
		}

		return null;
	}

	/**
	 * Gets the identifier of the object this tag points to
	 *
	 * @return string identifier
	 */
	public function GetObjectIdentifier()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->object;
	}

	/**
	 * Gets the commit this tag points to
	 *
	 * @return GitPHP_Commit commit for this tag
	 */
	public function GetCommit()
	{
		$hash = $this->GetCommitHash();
		if ($hash)
			return $this->GetProject()->GetCommit($hash);
	}

	/**
	 * Gets the hash of the commit this tag points to
	 *
	 * @return string commit hash for this tag
	 */
	public function GetCommitHash()
	{
		if (!$this->commitHash) {
			if ($this->type == 'commit') {
				$this->commitHash = $this->object;
			} else if ($this->type == 'tag') {
				$tag = $this->GetProject()->GetTagList()->GetTag($this->object);
				$this->commitHash = $tag->GetCommit()->GetHash();
			}
		}

		return $this->commitHash;
	}

	/**
	 * Sets the commit this tag points to
	 *
	 * @param GitPHP_Commit $commit commit object 
	 */
	public function SetCommit($commit)
	{
		if (!$commit)
			return;

		$this->SetCommitHash($commit->GetHash());
	}

	/**
	 * Sets the hash of the commit this tag points to
	 *
	 * @param string $hash hash
	 */
	public function SetCommitHash($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return;

		if (!$this->commitHash)
			$this->commitHash = $hash;
	}

	/**
	 * Gets the tag type
	 *
	 * @return string tag type
	 */
	public function GetType()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->type;
	}

	/**
	 * Gets the tagger
	 *
	 * @return string tagger
	 */
	public function GetTagger()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->tagger;
	}

	/**
	 * Gets the tagger epoch
	 *
	 * @return string tagger epoch
	 */
	public function GetTaggerEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerEpoch;
	}

	/**
	 * Gets the tagger local epoch
	 *
	 * @return string tagger local epoch
	 */
	public function GetTaggerLocalEpoch()
	{
		$epoch = $this->GetTaggerEpoch();
		$tz = $this->GetTaggerTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * Gets the tagger timezone
	 *
	 * @return string tagger timezone
	 */
	public function GetTaggerTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerTimezone;
	}

	/**
	 * Gets the tag age
	 *
	 * @return string age
	 */
	public function GetAge()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return time() - $this->taggerEpoch;
	}

	/**
	 * Gets the tag comment
	 *
	 * @return array comment lines
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
	}

	/**
	 * Tests if this is a light tag (tag without tag object)
	 *
	 * @return boolean true if tag is light (has no object)
	 */
	public function LightTag()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->object)
			return true;

		if (($this->type == 'commit') && ($this->object == $this->GetHash())) {
			return true;
		}

		return false;
	}

	/**
	 * Reads the tag data
	 */
	protected function ReadData()
	{
		$this->dataRead = true;

		list(
			$this->type,
			$this->object,
			$commitHash,
			$this->tagger,
			$this->taggerEpoch,
			$this->taggerTimezone,
			$this->comment
		) = $this->strategy->Load($this);

		if (!empty($commitHash))
			$this->commitHash = $commitHash;

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::CacheableDataChange);
		}
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
		$properties = array('dataRead', 'object', 'commitHash', 'type', 'tagger', 'taggerEpoch', 'taggerTimezone', 'comment');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Tag::CacheKey($this->project, $this->refName);
	}

	/**
	 * Gets tag's creation epoch (tagger epoch, or committer epoch for light tags)
	 *
	 * @return string creation epoch
	 */
	public function GetCreationEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($this->LightTag()) {
			$commit = $this->GetCommit();
			if (!empty($commit))
				return $commit->GetCommitterEpoch();
		}

		return $this->taggerEpoch;
	}

	/**
	 * Compares two tags by age
	 *
	 * @param GitPHP_Tag $a first tag
	 * @param GitPHP_Tag $b second tag
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$aObj = $a->GetObject();
		$bObj = $b->GetObject();
		if (($aObj instanceof GitPHP_Commit) && ($bObj instanceof GitPHP_Commit)) {
			return GitPHP_Commit::CompareAge($aObj, $bObj);
		}

		if ($aObj instanceof GitPHP_Commit)
			return 1;

		if ($bObj instanceof GitPHP_Commit)
			return -1;

		return strcmp($a->GetName(), $b->GetName());
	}

	/**
	 * Compares to tags by creation epoch
	 *
	 * @param GitPHP_Tag $a first tag
	 * @param GitPHP_Tag $b second tag
	 * @return integer comparison result
	 */
	public static function CompareCreationEpoch($a, $b)
	{
		$aEpoch = $a->GetCreationEpoch();
		$bEpoch = $b->GetCreationEpoch();

		if ($aEpoch == $bEpoch) {
			return 0;
		}

		return ($aEpoch < $bEpoch ? 1 : -1);
	}

	/**
	 * Generates a tag cache key
	 *
	 * @param mixed $proj project
	 * @param string $tag tag name
	 * @return string cache key
	 */
	public static function CacheKey($proj, $tag)
	{
		if (is_object($proj))
			return 'project|' . $proj->GetName() . '|tag|' . $tag;

		return 'project|' . $proj . '|tag|' . $tag;
	}

}
