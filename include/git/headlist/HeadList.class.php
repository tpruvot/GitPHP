<?php
/**
 * Class representing a list of heads
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\HeadList
 */
class GitPHP_HeadList extends GitPHP_RefList
{
	/**
	 * Data load strategy
	 *
	 * @var GitPHP_HeadListLoadStrategy_Interface
	 */
	protected $strategy;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 * @param GitPHP_HeadListLoadStrategy_Interface $strategy load strategy
	 */
	public function __construct($project, GitPHP_HeadListLoadStrategy_Interface $strategy)
	{
		parent::__construct($project);

		if (!$strategy)
			throw new Exception('Head list load strategy is required');

		$this->SetStrategy($strategy);
	}

	/**
	 * Set the load strategy
	 *
	 * @param GitPHP_HeadListLoadStrategy_Interface $strategy load strategy
	 */
	public function SetStrategy(GitPHP_HeadListLoadStrategy_Interface $strategy)
	{
		if (!$strategy)
			return;

		$this->strategy = $strategy;
	}

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
			$heads[] = $this->project->GetObjectManager()->GetHead($head, $hash);
		}

		return $heads;
	}

	/**
	 * Gets heads that point to a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return GitPHP_Head[] array of heads
	 */
	public function GetCommitHeads($commit)
	{
		if (!$commit)
			return array();

		$commitHash = $commit->GetHash();

		if (!$this->dataLoaded)
			$this->LoadData();

		if (!isset($this->invertedRefs[$commitHash])) return array();
		$headNames = $this->invertedRefs[$commitHash];
		$heads = array();

		foreach ($headNames as $head) {
			$heads[] = $this->project->GetObjectManager()->GetHead($head, $commitHash);
		}

		return $heads;
	}

	/**
	 * Load head data
	 */
	protected function LoadData()
	{
		$this->dataLoaded = true;

		$this->refs = $this->strategy->Load($this);
		foreach ($this->refs as $ref => $hash) $this->invertedRefs[$hash][] = $ref;
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

		return $this->project->GetObjectManager()->GetHead($head, $this->refs[$head]);
	}

	/**
	 * Gets heads in a specific order
	 *
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return GitPHP_Head[] array of heads
	 */
	public function GetOrderedHeads($order, $count = 0)
	{
		return $this->strategy->LoadOrdered($this, $order, $count);
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

		return $this->project->GetObjectManager()->GetHead(key($this->refs), current($this->refs));
	}

}
