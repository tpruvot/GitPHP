<?php
/**
 * Class representing a list of heads
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_HeadList extends GitPHP_RefList
{
	/**
	 * Gets the heads
	 *
	 * @return GitPHP_Head[] array of heads
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
	 * Load head data
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
	 * Gets a head
	 *
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
	 * Gets heads in a specific order
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
			$ordered = $this->GetOrderedRefsGit('heads', $order, $count, $skip);
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

	/**
	 * Get heads in a specific order using raw objects
	 *
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @param int $skip skip a number of results
	 * @return GitPHP_Head[] array of heads
	 */
	protected function GetOrderedHeadsRaw($order, $count = 0, $skip = 0)
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
			$heads = array_slice($heads, $skip, $count);
		}
		return $heads;
	}

	/**
	 * Returns the current head (overrides base)
	 *
	 * @return GitPHP_Head
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return $this->project->GetHead(key($this->refs), current($this->refs));
	}

}
