<?php
/**
 * Commit load strategy using git exe
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git\Commit
 */
class GitPHP_CommitLoad_Git extends GitPHP_CommitLoad_Base
{
	/**
	 * Gets the data for a commit
	 *
	 * @param GitPHP_Commit $commit commit
	 * @return array commit data
	 */
	public function Load($commit)
	{
		if (!$commit)
			return;

		$abbreviatedHash = null;
		$tree = null;
		$parents = array();
		$author = null;
		$authorEpoch = null;
		$authorTimezone = null;
		$committer = null;
		$committerEpoch = null;
		$committerTimezone = null;
		$title = null;
		$comment = array();

		$commitHash = $commit->GetHash();
		$projectPath = $commit->GetProject()->GetPath();

		/* Try to get abbreviated hash first try. Go up to max hash length on collision. */
		for ($i = 7; $i <= 40; $i++) {
			$abbreviatedHash = substr($commitHash, 0, $i);
			$ret = $this->exe->GetObjectData($projectPath, $abbreviatedHash);
			if (!$ret) return false;
			if ($ret['hash'] !== $commitHash) continue;
			$lines = explode("\n", $ret['contents']);
			break;
		}

		$linecount = count($lines);
		$i = 0;
		$encoding = null;

		/* Commit header */
		for ($i = 0; $i < $linecount; $i++) {
			$line = $lines[$i];
			if (preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Tree */
				$tree = $regs[1];
			} else if (preg_match('/^parent ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Parent */
				$parents[] = $regs[1];
			} else if (preg_match('/^author (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* author data */
				$author = $regs[1];
				$authorEpoch = $regs[2];
				$authorTimezone = $regs[3];
			} else if (preg_match('/^committer (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* committer data */
				$committer = $regs[1];
				$committerEpoch = $regs[2];
				$committerTimezone = $regs[3];
			} else if (preg_match('/^encoding (.+)$/', $line, $regs)) {
				$gitEncoding = trim($regs[1]);
				if ((strlen($gitEncoding) > 0) && function_exists('mb_list_encodings')) {
					$supportedEncodings = mb_list_encodings();
					$encIdx = array_search(strtolower($gitEncoding), array_map('strtolower', $supportedEncodings));
					if ($encIdx !== false) {
						$encoding = $supportedEncodings[$encIdx];
					}
				}
				$encoding = trim($regs[1]);
			} else if (strlen($line) == 0) {
				break;
			}
		}
		
		/* Commit body */
		for ($i += 1; $i < $linecount; $i++) {
			$trimmed = trim($lines[$i]);

			if ((strlen($trimmed) > 0) && (strlen($encoding) > 0) && function_exists('mb_convert_encoding')) {
				$trimmed = mb_convert_encoding($trimmed, 'UTF-8', $encoding);
			}

			if (empty($title) && (strlen($trimmed) > 0))
				$title = $trimmed;
			if (!empty($title)) {
				if ((strlen($trimmed) > 0) || ($i < ($linecount-1)))
					$comment[] = $trimmed;
			}
		}

		return array(
			$abbreviatedHash,
			$tree,
			$parents,
			$author,
			$authorEpoch,
			$authorTimezone,
			$committer,
			$committerEpoch,
			$committerTimezone,
			$title,
			$comment
		);

	}

	/**
	 * Whether this load strategy loads the abbreviated hash
	 *
	 * @return boolean
	 */
	public function LoadsAbbreviatedHash()
	{
		return true;
	}
}
