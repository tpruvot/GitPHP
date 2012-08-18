<?php
/**
 * Head list load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\HeadList
 */
class GitPHP_HeadListLoad_Git extends GitPHP_RefListLoad_Git implements GitPHP_HeadListLoadStrategy_Interface
{
	/**
	 * Loads the head list
	 *
	 * @param GitPHP_HeadList $headList head list
	 * @return array array of head hashes
	 */
	public function Load($headList)
	{
		$data = $this->GetRefs($headList, 'heads');
		return $data[0];
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

		$ordered = $this->GetOrderedRefs($headList, 'heads', $order, $count, $skip);

		if (!$ordered)
			return;

		$headObjs = array();
		foreach ($ordered as $head) {
			if ($headList->Exists($head)) {
				$headObjs[] = $headList->GetHead($head);
			}
		}

		return $headObjs;
	}
}
