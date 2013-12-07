<?php
/**
 * Ref list load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\RefList
 */
abstract class GitPHP_RefListLoad_Raw
{
	/**
	 * Loads the ref list for a ref type
	 *
	 * @param GitPHP_RefList $refList ref list
	 * @param string $type ref type
	 * @return array array of refs
	 */
	protected function GetRefs(GitPHP_RefList $refList, $type)
	{
		if (!$refList)
			return;

		if (empty($type))
			return;

		if (GitPHP_DebugLog::GetInstance()->GetEnabled())
			$autotimer = new GitPHP_DebugAutoLog();

		$refs = array();

		$prefix = 'refs/' . $type;
		$fullPath = $refList->GetProject()->GetPath() . '/' . $prefix;
		$fullPathLen = strlen($fullPath) + 1;

		/* loose files */
		$refFiles = GitPHP_Util::ListDir($fullPath);
		for ($i = 0; $i < count($refFiles); ++$i) {
			$ref = substr($refFiles[$i], $fullPathLen);

			if (empty($ref) || isset($refs[$ref]))
				continue;

			$hash = trim(file_get_contents($refFiles[$i]));
			if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
				$refs[$ref] = $hash;
			}
		}

		/* packed refs */
		if (file_exists($refList->GetProject()->GetPath() . '/packed-refs')) {
			$packedRefs = explode("\n", file_get_contents($refList->GetProject()->GetPath() . '/packed-refs'));

			foreach ($packedRefs as $ref) {

				if (preg_match('/^([0-9A-Fa-f]{40}) refs\/' . $type . '\/(.+)$/', $ref, $regs)) {
					if (!isset($refs[$regs[2]])) {
						$refs[$regs[2]] = $regs[1];
					}
				}
			}
		}

		return $refs;
	}
}
