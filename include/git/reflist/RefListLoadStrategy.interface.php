<?php
/**
 * Interface for ref list load strategies
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
interface GitPHP_RefListLoadStrategy_Interface
{
	/**
	 * Loads the ref list
	 *
	 * @param GitPHP_RefList $refList ref list
	 * @return array array of refs
	 */
	public function Load($refList);

	/** 
	 * Loads sorted refs
	 *
	 * @param GitPHP_RefList $refList ref list
	 * @param string $order list order
	 * @param integer $count number to load
	 */
	public function LoadOrdered($refList, $order, $count = 0);
}
