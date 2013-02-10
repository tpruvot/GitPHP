<?php
/**
 * Controller for getting a snapshot
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Snapshot extends GitPHP_ControllerBase
{

	/**
	 * Stores the archive object
	 *
	 * @var GitPHP_Archive
	 */
	private $archive = null;

	/**
	 * Snapshot cache directory
	 *
	 * @var string
	 */
	private $cacheDir = null;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeConfig();

		$this->InitializeUserList();

		$this->InitializeGitExe();

		$this->InitializeProjectList();

		// HACK: this needs to be done early because the snapshot controller modifies the headers before opening the archive
		if (!GitPHP_Util::FunctionAllowed('popen'))
			throw new GitPHP_DisabledFunctionException('popen');

		if (isset($this->params['project'])) {
			$project = $this->projectList->GetProject($this->params['project']);
			if (!$project) {
				throw new GitPHP_InvalidProjectParameterException($this->params['project']);
			}
			if ($this->userList && ($this->userList->GetCount() > 0)) {
				if (!$project->UserCanAccess((!empty($_SESSION['gitphpuser']) ? $_SESSION['gitphpuser'] : null))) {
					throw new GitPHP_UnauthorizedProjectException($this->params['project']);
				}
			}
			$this->project = $project->GetProject();
		}

		if (!$this->project) {
			throw new GitPHP_MissingProjectParameterException();
		}

		$this->preserveWhitespace = true;

		if (empty($this->params['format']))
			$this->params['format'] = $this->config->GetValue('compressformat');

		$this->InitializeArchive();

		if ($this->config->GetValue('cache')) {

			$this->cacheDir = GITPHP_CACHEDIR . 'snapshots/';

			if (file_exists($this->cacheDir)) {
				if (!is_dir($this->cacheDir)) {
					throw new Exception($this->cacheDir . ' exists but is not a directory');
				} else if (!is_writable($this->cacheDir)) {
					throw new Exception($this->cacheDir . ' is not writable');
				}
			} else {
				if (!mkdir($this->cacheDir, 0777))
					throw new Exception($this->cacheDir . ' could not be created');
				chmod($this->cacheDir, 0777);
			}
		}
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? $this->params['file'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '') . '|' . $this->params['format'];
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local && $this->resource) {
			return $this->resource->translate('snapshot');
		}
		return 'snapshot';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		$mimetype = $this->archive->GetMimeType();
		if (!empty($mimetype))
			$this->headers[] = 'Content-Type: ' . $mimetype;

		$this->headers[] = 'Content-Disposition: attachment; filename=' . $this->archive->GetFilename();

		if ($this->config->GetValue('cache')) {
			$cachedfile = $this->cacheDir . $this->CachedSnapshotFile();
			if (is_readable($cachedfile)) {
				$size = filesize($cachedfile);
				if ($size !== false)
					$this->headers[] = 'Content-Length: ' . $size;
			}
		}
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
	}

	/**
	 * Render this controller
	 */
	public function Render()
	{
		$cache = $this->config->GetValue('cache');
		$cachedfile = null;

		if ($cache) {
			$cachedfile = $this->CachedSnapshotFile();
			$cachedfilepath = $this->cacheDir . $cachedfile;
			if (is_readable($cachedfilepath)) {
				$cachehandle = fopen($cachedfilepath, 'rb');
				if ($cachehandle) {
					while (!feof($cachehandle)) {
						print fread($cachehandle, 1048576);
						flush();
					}
					fclose($cachehandle);
					return;
				}
			}
		}

		if ($this->archive->Open()) {

			$tmpcachefile = null;
			$cachehandle = null;

			if ($cache && !empty($cachedfile)) {
				// write cached file too
				$pid = 0;
				if (function_exists('posix_getpid'))
					$pid = posix_getpid();
				else
					$pid = rand();

				$tmpcachefile = 'tmp-' . $pid . '-' . $cachedfile;
				$cachehandle = fopen($this->cacheDir . $tmpcachefile, 'wb');
			}

			while (($data = $this->archive->Read()) !== false) {

				print $data;
				flush();

				if ($cache && $cachehandle) {
					fwrite($cachehandle, $data);
				}

			}

			$this->archive->Close();

			if ($cachehandle) {
				fclose($cachehandle);
				sleep(1);
				rename($this->cacheDir . $tmpcachefile, $this->cacheDir . $cachedfile);
			}
		}
	}

	/**
	 * Initialize archive for reading
	 */
	private function InitializeArchive()
	{
		$strategy = null;
		if ($this->params['format'] == GITPHP_COMPRESS_TAR) {
			$strategy = new GitPHP_Archive_Tar();
		} else if ($this->params['format'] == GITPHP_COMPRESS_BZ2) {
			$strategy = new GitPHP_Archive_Bzip2($this->config->GetValue('compresslevel'));
			if (!$strategy->Valid())
				$strategy = new GitPHP_Archive_Tar();
		} else if ($this->params['format'] == GITPHP_COMPRESS_GZ) {
			$strategy = new GitPHP_Archive_Gzip($this->config->GetValue('compresslevel'));
			if (!$strategy->Valid())
				$strategy = new GitPHP_Archive_Tar();
		} else if ($this->params['format'] == GITPHP_COMPRESS_ZIP) {
			$strategy = new GitPHP_Archive_Zip($this->config->GetValue('compresslevel'));
			if (!$strategy->Valid())
				$strategy = new GitPHP_Archive_Tar();
		}
		$strategy->SetExe($this->exe);

		$this->archive = new GitPHP_Archive($this->GetProject(), null, $strategy, (isset($this->params['file']) ? $this->params['file'] : ''), (isset($this->params['prefix']) ? $this->params['prefix'] : ''));
		$commit = null;

		if (!isset($this->params['hash']))
			$commit = $this->GetProject()->GetHeadCommit();
		else
			$commit = $this->GetProject()->GetCommit($this->params['hash']);

		$this->archive->SetObject($commit);
	}

	/**
	 * Gets the cached snapshot file name
	 *
	 * @return string cached file name
	 */
	private function CachedSnapshotFile()
	{
		$key = ($this->archive->GetObject() ? $this->archive->GetObject()->GetHash() : '') . '|' . (isset($this->params['file']) ? $this->params['file'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '');
		$cachefile = sha1($key) . '-' . $this->archive->GetFilename();
		return $cachefile;
	}

}
