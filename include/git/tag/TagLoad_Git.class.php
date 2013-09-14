<?php
/**
 * Tag load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Tag
 */
class GitPHP_TagLoad_Git implements GitPHP_TagLoadStrategy_Interface
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
	 * @param GitPHP_GitExe $exe executable
	 */
	public function __construct($exe)
	{
		if (!$exe)
			throw new Exception('Git exe is required');

		$this->exe = $exe;
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

		$result = $this->exe->GetObjectData($tag->GetProject()->GetPath(), $tag->GetHash());

		if ($result['type'] === 'commit') {
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

		/* get data from tag object */
		$result = $this->exe->GetObjectData($tag->GetProject()->GetPath(), $tag->GetName());

		$lines = explode("\n", $result['contents']);

		if (!isset($lines[0]))
			return;

		$objectHash = null;

		$readInitialData = false;
		foreach ($lines as $i => $line) {
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
				$args = array();
				$args[] = 'tag';
				$args[] = $objectHash;
				$ret = $this->exe->Execute($tag->GetProject()->GetPath(), GIT_CAT_FILE, $args);
				$lines = explode("\n", $ret);
				foreach ($lines as $i => $line) {
					if (preg_match('/^tag (.+)$/', $line, $regs)) {
						$name = trim($regs[1]);
						$object = $name;
					}
				}
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
