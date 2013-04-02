<?php
/**
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Blob extends GitPHP_FilesystemObject implements GitPHP_Observable_Interface, GitPHP_Cacheable_Interface
{

	/**
	 * Stores the file data
	 */
	protected $data;

	/**
	 * Stores whether data has been read
	 */
	protected $dataRead = false;

	/**
	 * Stores the size
	 */
	protected $size = null;

	/**
	 * Stores the history
	 */
	protected $history = array();

	/**
	 * Stores whether the history has been read
	 */
	protected $historyRead = false;

	/**
	 * Stores blame info
	 */
	protected $blame = array();

	/**
	 * Stores whether blame was read
	 */
	protected $blameRead = false;

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
	 * @return mixed blob object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * Gets the blob data
	 *
	 * @param boolean $explode true to explode data into an array of lines
	 * @return string blob data
	 */
	public function GetData($explode = false)
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($explode)
			return explode("\n", $this->data);
		else
			return $this->data;
	}

	/**
	 * Reads the blob data
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		if ($this->GetProject()->GetCompat()) {
			$args = array();
			$args[] = 'blob';
			$args[] = $this->hash;

			$this->data = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_CAT_FILE, $args);
		} else {
			$this->data = $this->GetProject()->GetObjectByType($this->hash, GitPHP_Pack::OBJ_BLOB);
		}

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

		if (!$this->dataRead)
			$this->ReadData();

		return strlen($this->data);
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

		$data = $this->data;
		if (strlen($this->data) > 8000)
			$data = substr($data, 0, 8000);

		return strpos($data, chr(0)) !== false;
	}

	/**
	 * Get the file mimetype
	 *
	 * @param boolean $short true to only the type group
	 * @return string mime
	 */
	public function FileMime($short = false)
	{
		$mime = $this->FileMime_Fileinfo();

		if (empty($mime))
			$mime = $this->FileMime_File();

		if (empty($mime))
			$mime = $this->FileMime_Extension();

		if ((!empty($mime)) && $short) {
			$mime = strtok($mime, '/');
		}

		return $mime;
	}

	/** 
	 * Get the file mimetype using fileinfo
	 *
	 * @return string mimetype
	 */
	private function FileMime_Fileinfo()
	{
		if (!function_exists('finfo_buffer'))
			return '';

		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->data)
			return '';

		$mime = '';

		$magicdb = GitPHP_Util::CleanPath(GitPHP_Config::GetInstance()->GetValue('magicdb', null));
		if (empty($magicdb)) {
			if (GitPHP_Util::IsWindows()) {
				$magicdb = GitPHP_Util::AddSlash(PHP_BINDIR) . 'extras/magic';
			} else {
				$magicdb = '/usr/share/misc/magic';
			}
		}

		$finfo = @finfo_open(FILEINFO_MIME, $magicdb);
		if ($finfo) {
			$mime = finfo_buffer($finfo, $this->data, FILEINFO_MIME);
			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
			}
			finfo_close($finfo);
		}

		return $mime;
	}

	/**
	 * Get the file mimetype using file command
	 *
	 * @return string mimetype
	 */
	private function FileMime_File()
	{
		if (GitPHP_Util::IsWindows()) {
			return '';
		}

		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->data)
			return '';

		$descspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w')
		);

		$proc = proc_open('file -b --mime -', $descspec, $pipes);
		if (is_resource($proc)) {
			fwrite($pipes[0], $this->data);
			fclose($pipes[0]);
			$mime = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);

			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
				return $mime;
			}
		}

		return '';
	}

	/**
	 * Get the file mimetype using the file extension
	 *
	 * @return string mimetype
	 */
	private function FileMime_Extension()
	{
		$file = $this->GetName();

		if (empty($file))
			return '';

		$dotpos = strrpos($file, '.');
		if ($dotpos !== FALSE)
			$file = substr($file, $dotpos+1);
		switch ($file) {
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
				break;
			case 'gif':
				return 'image/gif';
				break;
			case 'png';
				return 'image/png';
				break;
		}

		return '';
	}

	/**
	 * Gets the history of this file
	 *
	 * @return array array of filediff changes
	 */
	public function GetHistory()
	{
		if (!$this->historyRead)
			$this->ReadHistory();

		return $this->history;
	}

	/**
	 * Reads the file history
	 */
	private function ReadHistory()
	{
		$this->historyRead = true;

		$args = array();
		if ($this->commitHash)
			$args[] = $this->commitHash;
		else
			$args[] = 'HEAD';
		$args[] = '|';
		$args[] = GitPHP_GitExe::GetInstance()->GetBinary();
		$args[] = '--git-dir=' . escapeshellarg($this->GetProject()->GetPath());
		$args[] = GIT_DIFF_TREE;
		$args[] = '-r';
		$args[] = '--stdin';
		$args[] = '--';
		$args[] = $this->GetPath();
		
		$historylines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_REV_LIST, $args));

		$commitHash = null;
		foreach ($historylines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40})/', $line, $regs)) {
				$commitHash = $regs[1];
			} else if ($commitHash) {
				try {
					$history = new GitPHP_FileDiff($this->GetProject(), $line);
					$history->SetCommitHash($commitHash);
					$this->history[] = $history;
				} catch (Exception $e) {
				}
				$commitHash = null;
			}
		}
	}

	/**
	 * Gets blame info
	 *
	 * @return array blame array (line to commit mapping)
	 */
	public function GetBlame()
	{
		if (!$this->blameRead)
			$this->ReadBlame();

		return $this->blame;
	}

	/**
	 * Read blame info
	 */
	private function ReadBlame()
	{
		$this->blameRead = true;

		$args = array();
		$args[] = '-s';
		$args[] = '-l';
		if ($this->commitHash)
			$args[] = $this->commitHash;
		else
			$args[] = 'HEAD';
		$args[] = '--';
		$args[] = $this->GetPath();

		$blamelines = explode("\n", GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_BLAME, $args));

		$lastcommit = '';
		foreach ($blamelines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40})(\s+.+)?\s+([0-9]+)\)/', $line, $regs)) {
				if ($regs[1] != $lastcommit) {
					$this->blame[(int)($regs[3])] = $this->GetProject()->GetCommit($regs[1]);
					$lastcommit = $regs[1];
				}
			}
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
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('data', 'dataRead');

		return array_merge($properties, parent::__sleep());
	}

	/**
	 * Gets the cache key to use for this object
	 *
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Blob::CacheKey($this->GetProject()->GetProject(), $this->hash);
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
