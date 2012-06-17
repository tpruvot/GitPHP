<?php
/**
 * GitPHP HeadList
 *
 * Class representing a list of heads
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'RefList.class.php');

/**
 * HeadList
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_HeadList extends GitPHP_RefList
{
	/**
	 * GetHeads
	 *
	 * Gets the heads
	 *
	 * @access public
	 * @return array array of heads
	 */
	public function GetHeads()
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		$heads = array();

		foreach ($this->refs as $head => $hash) {
			$heads[] = $this->project->GetHead($head, $hash);
		}

		return $heads;
	}

	/**
	 * LoadData
	 *
	 * Load head data
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		if ($this->compat)
			$this->refs = $this->ReadRefListGit('heads');
		else
			$this->refs = $this->ReadRefListRaw('heads');
	}

	/**
	 * GetHead
	 *
	 * Gets a head
	 *
	 * @access public
	 * @param string $head head
	 */
	public function GetHead($head)
	{
		if (empty($head))
			return null;

		if (!$this->dataLoaded)
			$this->LoadData();

		if (!isset($this->refs[$head]))
			return;

		return $this->project->GetHead($head, $this->refs[$head]);
	}

	/**
	 * GetOrderedHeads
	 *
	 * Gets heads in a specific order
	 *
	 * @access public
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return array array of heads
	 */
	public function GetOrderedHeads($order, $count = 0)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		if ($this->compat) {
			$ordered = $this->GetOrderedRefsGit('heads', $order, $count);
			$heads = array();
			foreach ($ordered as $head) {
				if (isset($this->refs[$head])) {
					$heads[] = $this->project->GetHead($head, $this->refs[$head]);
				}
			}
			return $heads;
		} else {
			return $this->GetOrderedHeadsRaw($order, $count);
		}
	}

	/**
	 * GetOrderedHeadsRaw
	 *
	 * Get heads in a specific order
	 *
	 * @access public
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return array array of heads
	 */
	private function GetOrderedHeadsRaw($order, $count = 0)
	{
		$heads = array();
		foreach ($this->refs as $head => $hash) {
			$heads[] = $this->project->GetHead($head, $hash);
		}

		/* TODO add different orders */
		if ($order == '-committerdate') {
			usort($heads, array('GitPHP_Head', 'CompareAge'));
		}

		if (($count > 0) && (count($heads) > $count)) {
			$heads = array_slice($heads, 0, $count);
		}
		return $heads;
	}

	/**
	 * current
	 *
	 * Returns the current revision (overrides base)
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return $this->project->GetHead(key($this->refs), current($this->refs));
	}

}
