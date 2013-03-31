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
	 */
	private $archive = null;

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		$this->InitializeConfig();
		$this->InitializeProjectList();

		if (isset($this->params['project'])) {
			$project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $this->params['project']));
			if (!$project) {
				throw new GitPHP_InvalidProjectParameterException($this->params['project']);
			}
			$this->project = $project->GetProject();
		}

		if (!$this->project) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}

		$this->preserveWhitespace = true;

		if (empty($this->params['format']))
			$this->params['format'] = $this->config->GetValue('compressformat');

		$this->InitializeArchive();
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
		switch ($this->archive->GetFormat()) {
			case GITPHP_COMPRESS_TAR:
				$this->headers[] = 'Content-Type: application/x-tar';
				break;
			case GITPHP_COMPRESS_BZ2:
				$this->headers[] = 'Content-Type: application/x-bzip2';
				break;
			case GITPHP_COMPRESS_GZ:
				$this->headers[] = 'Content-Type: application/x-gzip';
				break;
			case GITPHP_COMPRESS_ZIP:
				$this->headers[] = 'Content-Type: application/x-zip';
				break;
			default:
				throw new Exception('Unknown compression type');
		}

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

		$cache = GitPHP_Config::GetInstance()->GetValue('cache', false);
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

	/**
	 * Initialize archive for reading
	 */
	private function InitializeArchive()
	{

		$this->archive = new GitPHP_Archive($this->GetProject(), null,
			$this->params['format'],
			(isset($this->params['path']) ? $this->params['path'] : ''),
			(isset($this->params['prefix']) ? $this->params['prefix'] : '')
		);

		$commit = null;

		if (!isset($this->params['hash']))
			$commit = $this->GetProject()->GetHeadCommit();
		else
			$commit = $this->GetProject()->GetCommit($this->params['hash']);

		$this->archive->SetObject($commit);
	}
}
