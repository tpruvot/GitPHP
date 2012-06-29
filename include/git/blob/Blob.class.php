<?php
/**
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\Blob
 */
class GitPHP_Blob extends GitPHP_FilesystemObject implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
{

	/**
	 * The blob data
	 *
	 * @var string
	 */
	protected $data;

	/**
	 * Whether data has been read
	 *
	 * @var boolean
	 */
	protected $dataRead = false;

	/**
	 * The blob size
	 *
	 * @var int
	 */
	protected $size = null;

	/**
	 * Whether data has been encoded for serialization
	 *
	 * @var boolean
	 */
	protected $dataEncoded = false;

	/**
	 * Observers
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Data load strategy
	 *
	 * @var GitPHP_BlobLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project the project
	 * @param string $hash object hash
	 * @param GitPHP_BlobLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($project, $hash, GitPHP_BlobLoadStrategy_Interface $strategy)
	{
		parent::__construct($project, $hash);

		if (!$strategy)
			throw new Exception('Blob load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Gets the blob data
	 *
	 * @param boolean $explode true to explode data into an array of lines
	 * @return string|string[] blob data
	 */
	public function GetData($explode = false)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($this->dataEncoded)
			$this->DecodeData();

		if ($explode)
			return explode("\n", $this->data);
		else
			return $this->data;
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_BlobLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_BlobLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Reads the blob data
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		$this->data = $this->strategy->Load($this);

		$this->dataEncoded = false;

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::CacheableDataChange);
		}
	}

	/**
	 * Gets the blob size
	 *
	 * @return integer size
	 */
	public function GetSize()
	{
		if ($this->size !== null) {
			return $this->size;
		}

		return strlen($this->GetData());
	}

	/**
	 * Sets the blob size
	 *
	 * @param integer $size size
	 */
	public function SetSize($size)
	{
		$this->size = $size;
	}

	/**
	 * Tests if this blob is a binary file
	 *
	 * @return boolean true if binary file
	 */
	public function IsBinary()
	{
		if (!$this->dataRead)
			$this->ReadData();

		$data = $this->GetData();
		if (strlen($data) > 8000)
			$data = substr($data, 0, 8000);

		return strpos($data, chr(0)) !== false;
	}

	/**
	 * Encodes data so it can be serialized safely
	 */
	private function EncodeData()
	{
		if ($this->dataEncoded)
			return;

		$this->data = base64_encode($this->data);

		$this->dataEncoded = true;
	}

	/**
	 * Decodes data after unserialization
	 */
	private function DecodeData()
	{
		if (!$this->dataEncoded)
			return;

		$this->data = base64_decode($this->data);

		$this->dataEncoded = false;
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
		if (!$this->dataEncoded)
			$this->EncodeData();

		$properties = array('data', 'dataRead', 'dataEncoded');

		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Blob::CacheKey($this->project->GetProject(), $this->hash);
	}

	/**
	 * Generates a blob cache key
	 *
	 * @param string $proj project
	 * @param string $hash hash
	 * @return string cache key
	 */
	public static function CacheKey($proj, $hash)
	{
		return 'project|' . $proj . '|blob|' . $hash;
	}

}
