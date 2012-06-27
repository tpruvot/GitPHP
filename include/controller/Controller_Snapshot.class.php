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
	 * Constructor
	 */
	public function __construct()
	{
		$this->config = GitPHP_Config::GetInstance();
		$this->InitializeProjectList();

		if (isset($_GET['p'])) {
			$project = $this->projectList->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$project) {
				throw new GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
			}
			$this->project = $project->GetProject();
		}

		if (!$this->project) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}

		$this->preserveWhitespace = true;

		$this->ReadQuery();
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
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['path']) ? $this->params['path'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '') . '|' . $this->params['format'];
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local) {
			return __('snapshot');
		}
		return 'snapshot';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
		if (isset($_GET['f'])) $this->params['path'] = $_GET['f'];
		if (isset($_GET['prefix'])) $this->params['prefix'] = $_GET['prefix'];
		if (isset($_GET['fmt']))
			$this->params['format'] = $_GET['fmt'];
		else
			$this->params['format'] = $this->config->GetValue('compressformat', GITPHP_COMPRESS_ZIP);
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
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
		$strategy->SetExe(GitPHP_GitExe::GetInstance());

		$this->archive = new GitPHP_Archive($this->GetProject(), null, $strategy, (isset($this->params['path']) ? $this->params['path'] : ''), (isset($this->params['prefix']) ? $this->params['prefix'] : ''));
		$commit = null;

		if (!isset($this->params['hash']))
			$commit = $this->GetProject()->GetHeadCommit();
		else
			$commit = $this->GetProject()->GetCommit($this->params['hash']);

		$this->archive->SetObject($commit);

		$mimetype = $strategy->MimeType();
		if (!empty($mimetype))
			$this->headers[] = 'Content-Type: ' . $mimetype;

		$this->headers[] = 'Content-Disposition: attachment; filename=' . $this->archive->GetFilename();
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
		$this->LoadData();

		$cache = $this->config->GetValue('cache', false);
		$cachehandle = false;
		$cachefile = '';
		if ($cache && is_dir(GITPHP_CACHEDIR)) {
			$key = ($this->archive->GetObject() ? $this->archive->GetObject()->GetHash() : '') . '|' . (isset($this->params['path']) ? $this->params['path'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '');
			$cachefile = sha1($key) . '-' . $this->archive->GetFilename();
			$cachedfilepath = GITPHP_CACHEDIR . $cachefile;

			if (file_exists($cachedfilepath)) {
				// read cached file
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

			$tmpcachefile = '';

			if ($cache && !empty($cachefile)) {
				// write cached file too
				$pid = 0;
				if (function_exists('posix_getpid'))
					$pid = posix_getpid();
				else
					$pid = rand();

				$tmpcachefile = 'tmp-' . $pid . '-' . $cachefile;
				$cachehandle = fopen(GITPHP_CACHEDIR . $tmpcachefile, 'wb');
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
				rename(GITPHP_CACHEDIR . $tmpcachefile, GITPHP_CACHEDIR . $cachefile);
			}
		}
	}

}
