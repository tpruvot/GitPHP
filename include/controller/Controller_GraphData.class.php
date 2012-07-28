<?php
/**
 * Controller for returning raw graph data
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_GraphData extends GitPHP_ControllerBase
{
	/**
	 * Initialize controller
	 */
	public function Initialize()
	{
		parent::Initialize();

		if (!$this->config->GetValue('graphs')) {
			throw new Exception('Graphing has been disabled');
		}

		$this->preserveWhitespace = true;
		$this->DisableLogging();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'graphdata.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return isset($this->params['graphtype']) ? $this->params['graphtype'] : '';
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		return 'graphdata';
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		$this->headers[] = 'Content-Type: application/json';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$head = $this->GetProject()->GetHeadCommit();

		$data = null;

		if ($this->params['graphtype'] == 'commitactivity') {

			$data = array();

			$commits = explode("\n", $this->exe->Execute($this->GetProject()->GetPath(), 'rev-list', array('--format=format:"%H %ct"', $head->GetHash())));
			foreach ($commits as $commit) {
				if (preg_match('/^([0-9a-fA-F]{40}) ([0-9]+)$/', $commit, $regs)) {
					$data[] = array('CommitEpoch' => (int)$regs[2]);
				}
			}

		} else if ($this->params['graphtype'] == 'languagedist') {

			$data = array();

			include_once(GITPHP_GESHIDIR . "geshi.php");
			$geshi = new GeSHi("",'php');

			$files = explode("\n", $this->exe->Execute($this->GetProject()->GetPath(), 'ls-tree', array('-r', '--name-only', $head->GetTree()->GetHash())));
			foreach ($files as $file) {
				$filename = GitPHP_Util::BaseName($file);
				$lang = GitPHP_Util::GeshiFilenameToLanguage($filename);
				if (empty($lang)) {
					$lang = $geshi->get_language_name_from_extension(substr(strrchr($filename, '.'), 1));
					if (empty($lang)) {
						$lang = 'Other';
					}
				}

				if (!empty($lang) && ($lang !== 'Other')) {
					$fulllang = $geshi->get_language_fullname($lang);
					if (!empty($fulllang))
						$lang = $fulllang;
				}

				if (isset($data[$lang])) {
					$data[$lang]++;
				} else {
					$data[$lang] = 1;
				}
			}

		}

		$this->tpl->assign('data', json_encode($data));
	}

}
