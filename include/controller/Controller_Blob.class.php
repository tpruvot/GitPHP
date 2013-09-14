<?php
/**
 * Controller for displaying a blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blob extends GitPHP_ControllerBase
{

	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (empty($this->params['hashbase']))
			$this->params['hashbase'] = 'HEAD';
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		if ($this->Plain())
			return 'blobplain.tpl';
		return 'blob.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
			return $this->resource->translate('blob');
		}
		return 'blob';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		if ($this->Plain()) {

			$this->DisableLogging();
			$this->preserveWhitespace = true;

			// XXX: Nasty hack to cache headers
			if (!$this->tpl->isCached('blobheaders.tpl', $this->GetFullCacheKey())) {
				if (isset($this->params['file']))
					$saveas = GitPHP_Util::BaseName($this->params['file']);
				else
					$saveas = $this->params['hash'] . ".txt";

				$headers = array();

				$mime = null;
				if ($this->config->GetValue('filemimetype')) {
					if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
						$commit = $this->GetProject()->GetCommit($this->params['hashbase']);
						$this->params['hash'] = $commit->GetTree()->PathToHash($this->params['file']);
						if (empty($this->params['hash']))
							throw new GitPHP_FileNotFoundException($this->params['file']);
					}

					$blob = $this->GetProject()->GetObjectManager()->GetBlob($this->params['hash']);
					if (!empty($this->params['file']))
						$blob->SetPath($this->params['file']);

					$mimeReader = new GitPHP_FileMimeTypeReader($blob, $this->GetMimeStrategy());
					$mime = trim($mimeReader->GetMimeType());
				}

				if ($mime)
					$headers[] = "Content-type: $mime; charset=UTF-8";
				else
					$headers[] = "Content-type: text/plain; charset=UTF-8";

				$headers[] = "Content-disposition: inline; filename=\"" . $saveas . "\"";

				$this->tpl->assign("blobheaders", serialize($headers));
			}
			$out = $this->tpl->fetch('blobheaders.tpl', $this->GetFullCacheKey());

			$this->headers = unserialize(trim($out));
		} else {
			parent::LoadHeaders();
		}

	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$commit = $this->GetProject()->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		$tree = $commit->GetTree();
		$this->tpl->assign('tree', $commit->GetTree());

		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = $tree->PathToHash($this->params['file']);
			if (empty($this->params['hash']))
				throw new GitPHP_FileNotFoundException($this->params['file']);
		}

		$blob = $this->GetProject()->GetObjectManager()->GetBlob($this->params['hash']);
		if (!empty($this->params['file']))
			$blob->SetPath($this->params['file']);
		$blob->SetCommit($commit);
		$this->tpl->assign('blob', $blob);

		if ($this->Plain()) {
			return;
		}

		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign('head', $head);

		if ($this->config->GetValue('filemimetype')) {
			$mimeReader = new GitPHP_FileMimeTypeReader($blob, $this->GetMimeStrategy());
			$mimetype = $mimeReader->GetMimeType(true);
			if ($mimetype == 'image') {
				$this->tpl->assign('datatag', true);
				$this->tpl->assign('mime', $mimeReader->GetMimeType());
				$this->tpl->assign('data', base64_encode($blob->GetData()));
				return;
			}
		}

		if ($this->config->GetValue('geshi')) {
			include_once(GITPHP_GESHIDIR . "geshi.php");
			if (class_exists('GeSHi')) {
				$geshi = new GeSHi("",'php');
				if ($geshi) {
					$lang = GitPHP_Util::GeshiFilenameToLanguage($blob->GetName());
					if (empty($lang)) {
						$lang = $geshi->get_language_name_from_extension(substr(strrchr($blob->GetName(),'.'),1));
					}
					if (!empty($lang)) {
						$geshi->enable_classes();
						$geshi->enable_strict_mode(GESHI_MAYBE);
						$geshi->set_source($blob->GetData());
						$geshi->set_language($lang);
						$geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
						$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
						$geshi->set_overall_id('blobData');
						$this->tpl->assign('geshiout', $geshi->parse_code());
						$this->tpl->assign('geshicss', $geshi->get_stylesheet());
						$this->tpl->assign('geshi', true);
						return;
					}
				}
			}
		}

		$this->tpl->assign('bloblines', $blob->GetData(true));
	}

	/**
	 * Get valid mime strategy
	 */
	private function GetMimeStrategy()
	{
		$strategy = new GitPHP_FileMimeType_Fileinfo($this->config->GetValue('magicdb'));
		if ($strategy->Valid())
			return $strategy;

		$strategy = new GitPHP_FileMimeType_FileExe();
		if ($strategy->Valid())
			return $strategy;

		$strategy = new GitPHP_FileMimeType_Extension();
		if ($strategy->Valid())
			return $strategy;
	}

	/**
	 * Tests whether we are outputting a plaintext blob
	 *
	 * @return boolean true if plaintext blob
	 */
	public function Plain()
	{
		if (isset($this->params['output']) && ($this->params['output'] == 'plain'))
			return true;

		return false;
	}

}
