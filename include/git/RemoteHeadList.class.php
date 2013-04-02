<?php
/**
 * Class representing a list of heads
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @copyright Copyright (c) 2013 Tanguy Pruvot
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_RemoteHeadList extends GitPHP_HeadList
{

	/**
	 * 'm' remote branches
	 * @var array
	 */
	protected $repo_m_refs;

	/**
	 * Load head data
	 * @override GitPHP_HeadList
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if ($this->compat)
			$this->refs = $this->ReadRefListGit('remotes');
		else
			$this->refs = $this->ReadRefListRaw('remotes');
	}

	/**
	 * Reads the list of refs using the git executable
	 * @override GitPHP_RefList
	 *
	 * @param string $type ref type
	 * @return array array of refs
	 */
	protected function ReadRefListGit($type)
	{
		if (empty($type))
			return;

		$args = array();
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_SHOW_REF, $args);

		$lines = explode("\n", $ret);

		$refs = array();
		$this->repo_m_refs = array();

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/' . $type . '\/([^^]+)(\^{})?$/', $line, $regs)) {
				if (substr_compare($regs[2], 'm/', 0, 2) == 0)
					$this->repo_m_refs[$regs[2]] = $regs[1];
				else
					$refs[$regs[2]] = $regs[1];
			}
		}
		return $refs;
	}

	/**
	 * Reads the list of refs using the raw git files
	 * @override GitPHP_RefList
	 *
	 * @param string $type ref type
	 * @return array array of refs
	 */
	protected function ReadRefListRaw($type)
	{
		$refs = array();
		$this->repo_m_refs = array();

		foreach (parent::ReadRefListRaw($type) as $head => $hash) {
			if (substr_compare($head, 'm/', 0, 2) == 0)
				$this->repo_m_refs[$head] = $hash;
			else
				$refs[$head] = $hash;
		}
		return $refs;
	}

	/**
	 * Gets heads in a specific order
	 * @override GitPHP_HeadList
	 *
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @param int $skip skip a number of results
	 * @return GitPHP_Head[] array of heads
	 */
	public function GetOrderedHeads($order, $count = 0, $skip = 0)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		if ($this->compat) {
			$ordered = $this->GetOrderedRefsGit('remotes', $order, $count, $skip);
			$heads = array();
			foreach ($ordered as $head) {
				if (isset($this->refs[$head])) {
					$heads[] = $this->project->GetHead($head, $this->refs[$head]);
				}
			}
			return $heads;
		} else {
			return $this->GetOrderedHeadsRaw($order, $count, $skip);
		}
	}

	/*
	 * Get Remote Head Name
	 *
	 * @param GitPHP_Head $head
	 * @return string
	 */
	public static function GetRemoteName($head)
	{
		$ref = $head->GetName();

		// exclude branch name
		$ar = explode('/',$ref);
		array_pop($ar);
		$remote = implode('/',$ar);

		return $remote;
	}

}
