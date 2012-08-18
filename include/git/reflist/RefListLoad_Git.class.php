<?php
/**
 * Ref list load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\RefList
 */
abstract class GitPHP_RefListLoad_Git
{
	/**
	 * Executable
	 *
	 * @var GitPHP_GitExe
	 */
	protected $exe;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitExe $exe git executable
	 */
	public function __construct($exe)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;
	}

	/**
	 * Loads the ref list for a ref type
	 *
	 * @param GitPHP_RefList $refList ref list
	 * @param string $type ref type
	 * @return array array of ref array and commit array
	 */
	protected function GetRefs($refList, $type)
	{
		if (!$refList)
			return;

		if (empty($type))
			return;

		$args = array();
		$args[] = '--' . $type;
		$args[] = '--dereference';
		$ret = $this->exe->Execute($refList->GetProject()->GetPath(), GIT_SHOW_REF, $args);

		$lines = explode("\n", $ret);

		$refs = array();
		$commits = array();

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/' . $type . '\/([^^]+)(\^{})?$/', $line, $regs)) {
				if (!empty($regs[3]) && ($regs[3] == '^{}')) {
					$commits[$regs[2]] = $regs[1];
				} else {
					$refs[$regs[2]] = $regs[1];
				}
			}
		}

		return array($refs, $commits);
	}

	/**
	 * Get refs in a specific order
	 *
	 * @param GitPHP_RefList $refList ref list
	 * @param string $type type of ref
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @param int $skip skip a number of results
	 * @return array array of refs
	 */
	protected function GetOrderedRefs($refList, $type, $order, $count = 0, $skip = 0)
	{
		if (!$refList)
			return;

		if (empty($type) || empty($order))
			return null;

		$args = array();
		$args[] = '--sort=' . $order;
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			if ($skip > 0) {
				$args[] = '--count=' . ($count + $skip);
			} else {
				$args[] = '--count=' . $count;
			}
		}
		$args[] = '--';
		$args[] = 'refs/' . $type;
		$ret = $this->exe->Execute($refList->GetProject()->GetPath(), GIT_FOR_EACH_REF, $args);

		$lines = explode("\n", $ret);

		$prefix = 'refs/' . $type . '/';
		$prefixLen = strlen($prefix);

		$refs = array();

		foreach ($lines as $ref) {
			$ref = substr($ref, $prefixLen);
			if (!empty($ref))
				$refs[] = $ref;
		}

		if ($skip > 0) {
			$refs = array_slice($refs, $skip);
		}

		return $refs;
	}

}
