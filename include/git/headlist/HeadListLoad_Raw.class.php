<?php
/**
 * Head list load strategy using raw objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\HeadList
 */
class GitPHP_HeadListLoad_Raw extends GitPHP_RefListLoad_Raw implements GitPHP_HeadListLoadStrategy_Interface
{
	/**
	 * Loads the head list
	 *
	 * @param GitPHP_HeadList $headList head list
	 * @return array array of head hashes
	 */
	public function Load($headList)
	{
		return $this->GetRefs($headList, 'heads');
	}

	/** 
	 * Loads sorted heads
	 *
	 * @param GitPHP_HeadList $headList head list
	 * @param string $order list order
	 * @param integer $count number to load
	 * @param integer $skip number to skip
	 */
	public function LoadOrdered($headList, $order, $count = 0, $skip = 0)
	{
		if (!$headList)
			return;

		if (empty($order))
			return;

		if (GitPHP_DebugLog::GetInstance()->GetEnabled())
			$autotimer = new GitPHP_DebugAutoLog();

		$heads = $headList->GetHeads();

		/* TODO add different orders */
		if ($order == '-committerdate') {
			@usort($heads, array('GitPHP_Head', 'CompareAge'));
		}

		if ((($count > 0) && (count($heads) > $count)) || ($skip > 0)) {
			if ($count > 0)
				$heads = array_slice($heads, $skip, $count);
			else
				$heads = array_slice($heads, $skip);
		}

		return $heads;
	}
}
