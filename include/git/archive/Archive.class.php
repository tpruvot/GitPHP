<?php
/**
 * Generates an archive (snapshot)
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git\Archive
 */
class GitPHP_Archive
{
	/**
	 * The object type for this archive
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * The object hash for this archive
	 *
	 * @var string
	 */
	protected $objectHash;

	/**
	 * The project for this archive
	 *
	 * @var GitPHP_Project|string
	 */
	protected $project;

	/**
	 * The archive filename
	 *
	 * @var string
	 */
	protected $fileName = '';

	/**
	 * The archive path
	 *
	 * @var string
	 */
	protected $path = '';

	/**
	 * The archive prefix
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Archiving strategy
	 *
	 * @var GitPHP_ArchiveStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Instantiates object
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_Commit|GitPHP_Tree $gitObject the object
	 * @param GitPHP_ArchiveStrategy_Interface $strategy archive format strategy
	 * @param string $path subtree path to archive
	 * @param string $prefix archive directory prefix
	 */
	public function __construct($project, $gitObject, GitPHP_ArchiveStrategy_Interface $strategy, $path = '', $prefix = '')
	{
		$this->SetProject($project);
		$this->SetObject($gitObject);
		if (!$this->project && $gitObject) {
			$this->project = $gitObject->GetProject();
		}
		$this->SetPath($path);
		$this->SetPrefix($prefix);

		if (!$strategy)
			throw new Exception('Archiving strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the archive strategy
	 *
	 * @param GitPHP_ArchiveStrategy_Interface $strategy archive strategy
	 */
	public function SetStrategy(GitPHP_ArchiveStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

	/**
	 * Gets the object for this archive
	 *
	 * @return GitPHP_Commit|GitPHP_Tree|null the git object
	 */
	public function GetObject()
	{
		if ($this->objectType == 'commit') {
			return $this->GetProject()->GetCommit($this->objectHash);
		}

		if ($this->objectType = 'tree') {
			return $this->GetProject()->GetObjectManager()->GetTree($this->objectHash);
		}

		return null;
	}

	/**
	 * Sets the object for this archive
	 *
	 * @param GitPHP_Commit|GitPHP_Tree|null $object the git object
	 */
	public function SetObject($object)
	{
		// Archive only works for commits and trees

		if ($object == null) {
			$this->objectHash = '';
			$this->objectType = '';
			return;
		}

		if ($object instanceof GitPHP_Commit) {
			$this->objectType = 'commit';
			$this->objectHash = $object->GetHash();
			return;
		}

		if ($object instanceof GitPHP_Tree) {
			$this->objectType = 'tree';
			$this->objectHash = $object->GetHash();
			return;
		}

		throw new Exception('Invalid source object for archive');
	}

	/**
	 * Gets the project for this archive
	 *
	 * @return GitPHP_Project the project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Sets the project for this archive
	 *
	 * @param GitPHP_Project $project the project
	 */
	public function SetProject($project)
	{
		$this->project = $project;
	}

	/**
	 * Gets the filename for this archive
	 *
	 * @return string filename
	 */
	public function GetFilename()
	{
		if (!empty($this->fileName)) {
			return $this->fileName;
		}

		$fname = $this->GetProject()->GetSlug();

		if (!empty($this->path)) {
			$fname .= '-' . GitPHP_Util::MakeSlug($this->path);
		}

		if (!empty($this->objectHash)) {
			$fname .= '-' . $this->GetProject()->AbbreviateHash($this->objectHash);
		}

		$fname .= '.' . $this->strategy->Extension();

		return $fname;
	}

	/**
	 * Sets the filename for this archive
	 *
	 * @param string $name filename
	 */
	public function SetFilename($name = '')
	{
		$this->fileName = $name;
	}

	/**
	 * Gets the path to restrict this archive to
	 *
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * Sets the path to restrict this archive to
	 *
	 * @param string $path path to restrict
	 */
	public function SetPath($path = '')
	{
		$this->path = $path;
	}

	/**
	 * Gets the directory prefix to use for files in this archive
	 *
	 * @return string prefix
	 */
	public function GetPrefix()
	{
		if (!empty($this->prefix)) {
			return $this->prefix;
		}

		$pfx = $this->GetProject()->GetSlug() . '/';

		if (!empty($this->path))
			$pfx .= $this->path . '/';

		return $pfx;
	}

	/**
	 * Sets the directory prefix to use for files in this archive
	 *
	 * @param string $prefix prefix to use
	 */
	public function SetPrefix($prefix = '')
	{
		if (empty($prefix)) {
			$this->prefix = $prefix;
			return;
		}

		if (substr($prefix, -1) != '/') {
			$prefix .= '/';
		}

		$this->prefix = $prefix;
	}

	/**
	 * Gets the mime type for this archive
	 *
	 * @return string mime type
	 */
	public function GetMimeType()
	{
		return $this->strategy->MimeType();
	}

	/**
	 * Opens a descriptor for reading archive data
	 *
	 * @return boolean true on success
	 */
	public function Open()
	{
		if (!$this->objectHash)
		{
			throw new Exception('Invalid object for archive');
		}

		return $this->strategy->Open($this);
	}

	/**
	 * Close the archive data descriptor
	 *
	 * @return boolean true on success
	 */
	public function Close()
	{
		return $this->strategy->Close();
	}

	/**
	 * Read a chunk of the archive data
	 *
	 * @param int $size size of data to read
	 * @return string archive data
	 */
	public function Read($size = 1048576)
	{
		return $this->strategy->Read($size);
	}

	/**
	 * Gets the supported formats for the archiver
	 *
	 * @return array array of formats mapped to extensions
	 */
	public static function SupportedFormats()
	{
		$formats = array();

		$strategy = new GitPHP_Archive_Tar();
		if ($strategy->Valid())
			$formats[GITPHP_COMPRESS_TAR] = $strategy->Extension();
	
		$strategy = new GitPHP_Archive_Zip();
		if ($strategy->Valid())
			$formats[GITPHP_COMPRESS_ZIP] = $strategy->Extension();

		$strategy = new GitPHP_Archive_Bzip2();
		if ($strategy->Valid())
			$formats[GITPHP_COMPRESS_BZ2] = $strategy->Extension();

		$strategy = new GitPHP_Archive_Gzip();
		if ($strategy->Valid())
			$formats[GITPHP_COMPRESS_GZ] = $strategy->Extension();

		return $formats;
	}

}
