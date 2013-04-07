<?php
/**
 * Tag load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tag
 */
class GitPHP_TagLoad_Raw implements GitPHP_TagLoadStrategy_Interface
{
	/**
	 * Object loader
	 *
	 * @var GitPHP_GitObjectLoader
	 */
	protected $objectLoader;

	/**
	 * Constructor
	 *
	 * @param GitPHP_GitObjectLoader $objectLoader object loader
	 */
	public function __construct($objectLoader)
	{
		if (!$objectLoader)
			throw new Exception('Object loader is required');

		$this->objectLoader = $objectLoader;
	}

	/**
	 * Gets the data for a tag
	 *
	 * @param GitPHP_Tag $tag tag
	 * @return array array of tag data
	 */
	public function Load($tag)
	{
		if (!$tag)
			return;

		$type = null;
		$object = null;
		$commitHash = null;
		$tagger = null;
		$taggerEpoch = null;
		$taggerTimezone = null;
		$comment = array();

		$data = $this->objectLoader->GetObject($tag->GetHash(), $packedType);
		
		if ($packedType == GitPHP_Pack::OBJ_COMMIT) {
			/* light tag */
			$object = $tag->GetHash();
			$commitHash = $tag->GetHash();
			$type = 'commit';
			return array(
				$type,
				$object,
				$commitHash,
				$tagger,
				$taggerEpoch,
				$taggerTimezone,
				$comment
			);
		}

		$lines = explode("\n", $data);

		if (!isset($lines[0]))
			return;

		$objectHash = null;

		$readInitialData = false;
		foreach ($lines as $line) {
			if (!$readInitialData) {
				if (preg_match('/^object ([0-9a-fA-F]{40})$/', $line, $regs)) {
					$objectHash = $regs[1];
					continue;
				} else if (preg_match('/^type (.+)$/', $line, $regs)) {
					$type = $regs[1];
					continue;
				} else if (preg_match('/^tag (.+)$/', $line, $regs)) {
					continue;
				} else if (preg_match('/^tagger (.*) ([0-9]+) (.*)$/', $line, $regs)) {
					$tagger = $regs[1];
					$taggerEpoch = $regs[2];
					$taggerTimezone = $regs[3];
					continue;
				}
			}

			$trimmed = trim($line);

			if ((strlen($trimmed) > 0) || ($readInitialData === true)) {
				$comment[] = $line;
			}
			$readInitialData = true;
		}

		switch ($type) {
			case 'commit':
				$object = $objectHash;
				$commitHash = $objectHash;
				break;
			case 'tag':
				$object = $tag->GetProject()->GetTagList()->GetTagNameFromHash($objectHash);
				break;
			case 'blob':
				$object = $objectHash;
				break;
		}

		return array(
			$type,
			$object,
			$commitHash,
			$tagger,
			$taggerEpoch,
			$taggerTimezone,
			$comment
		);
	}
}
