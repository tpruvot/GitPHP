<?php
/**
 * GitPHP File Diff
 *
 * Represents a single file difference
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_BASEDIR . 'lib/php-diff/lib/Diff.php');
require_once(GITPHP_BASEDIR . 'lib/php-diff/lib/Diff/Renderer/Text/Unified.php');

class GitPHP_FileDiff
{
	/**
	 * Stores whether diff info has been read
	 */
	protected $diffInfoRead = false;

	/**
	 * Stores whether diff data has been read
	 */
	protected $diffDataRead = false;

	/**
	 * Stores the diff data
	 */
	protected $diffData;

	/**
	 * Stores whether split diff data has been read
	 */
	protected $diffDataSplitRead = false;

	/**
	 * Stores the diff data split up by left/right changes
	 */
	protected $diffDataSplit;

	/**
	 * Filename used on last data diff
	 */
	protected $diffDataName;

	/**
	 * Stores the from file mode
	 */
	protected $fromMode;

	/**
	 * Stores the to file mode
	 */
	protected $toMode;

	/**
	 * Stores the from hash
	 */
	protected $fromHash;

	/**
	 * Stores the to hash
	 */
	protected $toHash;

	/**
	 * Stores the status
	 */
	protected $status;

	/**
	 * Stores the similarity
	 */
	protected $similarity;

	/**
	 * Stores the from filename
	 */
	protected $fromFile;

	/**
	 * Stores the to filename
	 */
	protected $toFile;

	/**
	 * Stores the from file type
	 */
	protected $fromFileType;

	/**
	 * Stores the to file type
	 */
	protected $toFileType;

	/**
	 * Stores the project name
	 */
	protected $project;

	/**
	 * Stores the hash of the commit that caused this filediff
	 */
	protected $commitHash;

	/* set from parent TreeDiff --numstat */
	public $totAdd=0;
	public $totDel=0;

	/* count of diff blocs for <a names> */
	protected $diffCount=0;

	/* used for pictures in treediff */
	public $isPicture=false;

	/**
	 * Constructor
	 *
	 * @param mixed $project project
	 * @param string $fromHash source hash, can also be a diff-tree info line
	 * @param string $toHash target hash, required if $fromHash is a hash
	 * @throws Exception on invalid parameters
	 */
	public function __construct($project, $fromHash, $toHash = '')
	{
		$this->project = $project->GetProject();

		if ($this->ParseDiffTreeLine($fromHash))
			return;

		if (!(preg_match('/^[0-9a-fA-F]{40}$/', $fromHash) && preg_match('/^[0-9a-fA-F]{40}$/', $toHash))) {
			throw new Exception('Invalid parameters for FileDiff');
		}

		$this->fromHash = $fromHash;
		$this->toHash = $toHash;
	}

	/**
	 * Gets the project
	 *
	 * @return mixed project
	 */
	public function GetProject()
	{
		return GitPHP_ProjectList::GetInstance()->GetProject($this->project);
	}

	/**
	 * ParseDiffTreeLine
	 *
	 * @param string $diffTreeLine line from difftree
	 * @return boolean true if data was read from line
	 */
	private function ParseDiffTreeLine($diffTreeLine)
	{
		if (preg_match('/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/', $diffTreeLine, $regs)) {
			$this->diffInfoRead = true;

			$this->fromMode = $regs[1];
			$this->toMode = $regs[2];
			$this->fromHash = $regs[3];
			$this->toHash = $regs[4];
			$this->status = $regs[5];
			$this->similarity = ltrim($regs[6], '0');
			$this->fromFile = strtok($regs[7], "\t");
			$this->toFile = strtok("\t");
			if ($this->toFile === false) {
				/* no filename change */
				$this->toFile = $this->fromFile;
			}

			return true;
		}

		return false;
	}

	/**
	 * Reads file diff info
	 */
	protected function ReadDiffInfo()
	{
		$this->diffInfoRead = true;

		/* TODO: read a single difftree line on-demand */
	}

	/**
	 * Gets the from file mode
	 * (full a/u/g/o)
	 *
	 * @return string from file mode
	 */
	public function GetFromMode()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return $this->fromMode;
	}

	/**
	 * Gets the from file mode in short form
	 * (standard u/g/o)
	 *
	 * @return string short from file mode
	 */
	public function GetFromModeShort()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return substr($this->fromMode, -4);
	}

	/**
	 * Gets the to file mode
	 * (full a/u/g/o)
	 *
	 * @return string to file mode
	 */
	public function GetToMode()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return $this->toMode;
	}

	/**
	 * Gets the to file mode in short form
	 * (standard u/g/o)
	 *
	 * @return string short to file mode
	 */
	public function GetToModeShort()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return substr($this->toMode, -4);
	}

	/**
	 * Gets the from hash
	 *
	 * @return string from hash
	 */
	public function GetFromHash()
	{
		return $this->fromHash;
	}

	/**
	 * Gets the to hash
	 *
	 * @return string to hash
	 */
	public function GetToHash()
	{
		return $this->toHash;
	}

	/**
	 * Gets the from file blob
	 *
	 * @return mixed blob object
	 */
	public function GetFromBlob()
	{
		if (empty($this->fromHash))
			return null;

		return $this->GetProject()->GetBlob($this->fromHash);
	}

	/**
	 * Gets the to file blob
	 *
	 * @return mixed blob object
	 */
	public function GetToBlob()
	{
		if (empty($this->toHash))
			return null;

		return $this->GetProject()->GetBlob($this->toHash);
	}

	/**
	 * Gets the status of the change
	 *
	 * @return string status
	 */
	public function GetStatus()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return $this->status;
	}

	/**
	 * Gets the similarity
	 *
	 * @return string similarity
	 */
	public function GetSimilarity()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return $this->similarity;
	}

	/**
	 * Gets the from file name
	 *
	 * @return string from file
	 */
	public function GetFromFile($urlencode='')
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		if ($urlencode == 'f') {
			return GitPHP_Util::UrlEncodeFilePath($this->fromFile);
		}

		return $this->fromFile;
	}

	/**
	 * Gets the to file name
	 *
	 * @return string to file
	 */
	public function GetToFile($urlencode='')
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		if ($urlencode == 'f') {
			return GitPHP_Util::UrlEncodeFilePath($this->toFile);
		}

		return $this->toFile;
	}

	/**
	 * Gets the from file type
	 *
	 * @return int from file type
	 */
	public function GetFromFileType()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return GitPHP_FilesystemObject::ObjectType($this->fromMode);
	}

	/**
	 * Gets the to file type
	 *
	 * @return int to file type
	 */
	public function GetToFileType()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return GitPHP_FilesystemObject::ObjectType($this->toMode);
	}

	/**
	 * Gets the from label
	 *
	 * @param string $file override file name
	 * @return string from label
	 */
	public function GetFromLabel($file = null)
	{
		if ($this->status == 'A')
			return '/dev/null';

		if (!empty($file))
			return 'a/' . $file;

		$fromFile = $this->GetFromFile();
		if (!empty($fromFile))
			return 'a/' . $fromFile;

		return 'a/' . $this->GetFromHash();
	}

	/**
	 * Gets the to label
	 *
	 * @param string $file override file name
	 * @return string to label
	 */
	public function GetToLabel($file = null)
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		if ($this->status == 'D')
			return '/dev/null';

		if (!empty($file))
			return 'b/' . $file;

		$toFile = $this->GetToFile();
		if (!empty($toFile))
			return 'b/' . $toFile;

		return 'b/' . $this->GetToHash();
	}

	/**
	 * Gets whether one or both files are binary files
	 *
	 * @return boolean true if binary
	 */
	public function IsBinary()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		if (($this->status != 'A') && $this->GetFromBlob()->IsBinary())
			return true;

		if (($this->status != 'D') && $this->GetToBlob()->IsBinary())
			return true;

		return false;
	}

	/**
	 * Tests if filetype changed
	 *
	 * @return boolean true if file type changed
	 */
	public function FileTypeChanged()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0x17000) != (octdec($this->toMode) & 0x17000);
	}

	/**
	 * Tests if file mode changed
	 *
	 * @return boolean true if file mode changed
	 */
	public function FileModeChanged()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0777) != (octdec($this->toMode) & 0777);
	}

	/**
	 * Tests if the from file is a regular file
	 *
	 * @return boolean true if from file is regular
	 */
	public function FromFileIsRegular()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0x8000) == 0x8000;
	}

	/**
	 * Tests if the to file is a regular file
	 *
	 * @return boolean true if to file is regular
	 */
	public function ToFileIsRegular()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->toMode) & 0x8000) == 0x8000;
	}

	/**
	 * Gets the diff output
	 *
	 * @param string $file override the filename on the diff
	 * @return string diff output
	 */
	public function GetDiff($file = '', $readFileData = true, $explode = false)
	{
		if ($this->diffDataRead && ($file == $this->diffDataName)) {
			if ($explode)
				return explode("\n", $this->diffData);
			else
				return $this->diffData;
		}

		if ((!$this->diffInfoRead) && $readFileData)
			$this->ReadDiffInfo();

		$this->diffDataName = $file;
		$this->diffDataRead = true;

		if ((!empty($this->status)) && ($this->status != 'A') && ($this->status != 'D') && ($this->status != 'M')) {
			$this->diffData = '';
			return;
		}

		$this->diffData = $this->GetDiffData(3, true, $file);

		if ($explode)
			return explode("\n", $this->diffData);
		else
			return $this->diffData;
	}

	/**
	 * construct the side by side diff data from the git data
	 * The result is an array of ternary arrays with 3 elements each:
	 * First the mode ("" or "-added" or "-deleted" or "-modified"),
	 * then the first column, then the second.
	 *
	 * @author Mattias Ulbrich
	 * @author Tanguy Pruvot
	 *
	 * @return an array of line elements (see above)
	 */
	public function GetDiffSplit()
	{
		if ($this->diffDataSplitRead) {
			return $this->diffDataSplit;
		}

		$this->diffDataSplitRead = true;

		$fromBlob = $this->GetFromBlob();
		$blob = $fromBlob->GetData(true);
		unset($fromBlob);

		$diffLines = explode("\n", $this->GetDiffData(0, false));

		//
		// parse diffs
		$diffs = array();
		$currentDiff = FALSE;
		$totAdd = 0; $totDel = 0; $idx = 0;
		foreach($diffLines as $d) {
			$d = trim($d);
			if(strlen($d) == 0)
				continue;
			switch($d[0]) {
				case '@':
					if($currentDiff) {
						if (count($currentDiff['left']) == 0 && count($currentDiff['right']) > 0) {
							if ($this->UseXDiff()) {
								$currentDiff['line']++; 	// HACK to make added blocks align correctly
							}
						}
						$diffs[] = $currentDiff;
					}
					$comma = strpos($d, ",");
					$line = -intval(substr($d, 2, $comma-2));
					$lastDeleted = false;
					$currentDiff = array("line" => $line,
						"left" => array(), "right" => array());
					break;
				case '+':
					if($currentDiff) {
						$currentDiff["right"][] = substr($d, 1);
						$totAdd++;
					}
					break;
				case '-':
					if($currentDiff) {
						$currentDiff["left"][] = substr($d, 1);
						$totDel++;
					}
					break;
				case ' ':
					echo "should not happen!";
					if($currentDiff) {
						$currentDiff["left"][] = substr($d, 1);
						$currentDiff["right"][] = substr($d, 1);
					}
					break;
			}
		}
		if($currentDiff) {
			if (count($currentDiff['left']) == 0 && count($currentDiff['right']) > 0) {
				if ($this->UseXDiff()) {
					$currentDiff['line']++;		// HACK to make added blocks align correctly
				}
			}
			$diffs[] = $currentDiff;
		}

		// equals to git diff --numstat
		$this->totAdd = $totAdd;
		$this->totDel = $totDel;

		// + 10 000 lines file... require to skip unchanged source
		$big_file = (count($blob) > 10000);

		//
		// iterate over diffs
		$output = array();
		$lnl = 0; $lnr = 0; $num = 0;
		$big_after = 15;

		foreach($diffs as $d) {

			$big_before = $big_after = 15;

			while($lnl+1 < $d['line']) {
				if (!$big_file || ($lnl+$big_before+1 == $d['line'])) {
					$h = $blob[$lnl];
					$output[] = array('', $h, $h, $lnl, $lnr, FALSE);
					$big_before--;
				}
				$lnl++; $lnr++;
			}

			if(empty($d['left'])) {
				$mode = 'added';
				$num++;
			} elseif(empty($d['right'])) {
				$mode = 'deleted';
				$num++;
			} else {
				$mode = 'modified';
				$num++;
			}
			$disp_num = $num;

			$nbl = count($d['left']);
			$nbr = count($d['right']);
			$cnt = max( $nbl, $nbr );
			for($i = 0; $i < $cnt; $i++) {
				if ($i < $nbl) {
					$left = $d['left'][$i];
					$disp_l = ++$lnl;
				} else {
					$left = FALSE;
					$disp_l = FALSE;
				}
				if ($i < $nbr) {
					$right = $d['right'][$i];
					$disp_r = ++$lnr;
				} else {
					$right = FALSE;
					$disp_r = FALSE;
				}
				$output[] = array($mode, $left, $right, $disp_l, $disp_r, $disp_num);

				//only set Diff number on first line of diff.
				$disp_num = FALSE;
			}
		}

		while ($lnl < count($blob)) {
			if (!$big_file || $big_after-- > 0) {
				$output[] = array('', $blob[$lnl], $blob[$lnl], $lnl, ++$lnr, FALSE);
			}
			$lnl++;
		}

		$this->diffCount = $num;

		$this->diffDataSplit = $output;
		return $output;
	}

	/**
	 * @return int total of diff blocs
	 */
	public function GetDiffCount() {
		$this->GetDiffSplit();
		return $this->diffCount;
	}

	/**
	 * Ensure totAdd & totDel are assigned (tpruvot)
	 *
	 * @return int total of line changes
	 */
	public function GetStats() {

		//we can use the cmdline with --numstat use GetDiffSplit()
		//or could be already set by TreeDiff parent

		$tot = $this->totAdd + $this->totDel;
		if ($this->diffDataSplitRead || $tot > 0) {
			return $tot;
		} else {
			//todo cmdline ?
			$this->GetDiffSplit();
			return $this->totAdd + $this->totDel;
		}
	}

	/**
	 * Get diff data
	 *
	 * @param integer $context number of context lines
	 * @param boolean $header true to include file header
	 * @param string $file override file name
	 * @return string diff data
	 */
	private function GetDiffData($context = 3, $header = true, $file = null)
	{
		$fromData = '';
		$toData = '';
		if (empty($this->status) || ($this->status == 'M') || ($this->status == 'D')) {
			$fromBlob = $this->GetFromBlob();
			$fromData = $fromBlob->GetData(false);
			unset($fromBlob);
		}
		if (empty($this->status) || ($this->status == 'M') || ($this->status == 'A')) {
			$toBlob = $this->GetToBlob();
			$toData = $toBlob->GetData(false);
			unset($toBlob);
		}
		$output = '';
		if ($this->IsBinary()) {
			$output = sprintf(__('Binary files %1$s and %2$s differ'), $this->GetFromLabel($file), $this->GetToLabel($file)) . "\n";
		} else {
			if ($header) {
				$output = '--- ' . $this->GetFromLabel($file) . "\n" . '+++ ' . $this->GetToLabel($file) . "\n";
			}

			$cacheKey = 'project|' . $this->project . '|diff|' . $context . '|' . $this->fromHash . '|' . $this->toHash;
			$diffOutput = GitPHP_Cache::GetObjectCacheInstance()->Get($cacheKey);
			if ($diffOutput === false) {

				if ($this->UseXDiff()) {
					$diffOutput = $this->GetXDiff($fromData, $toData, $context);
				} else {
					$diffOutput = $this->GetPhpDiff($fromData, $toData, $context);
				}

				GitPHP_Cache::GetObjectCacheInstance()->Set($cacheKey, $diffOutput);
			}
			$output .= $diffOutput;

		}
		return $output;
	}

	/**
	 * Get diff using php-diff
	 *
	 * @param string $fromData from file data
	 * @param string $toData to file data
	 * @param integer $context context lines
	 * @return string diff content
	 */
	private function GetPhpDiff($fromData, $toData, $context = 3)
	{
		$options = array('context' => $context);

		$diffObj = new Diff(explode("\n", $fromData), explode("\n", $toData), $options);
		$renderer = new Diff_Renderer_Text_Unified;
		return $diffObj->render($renderer);
	}

	/**
	 * Returns whether xdiff should be used
	 *
	 * @return boolean true if xdiff should be used
	 */
	private function UseXDiff()
	{
		return function_exists('xdiff_string_diff');
	}

	/**
	 * Get diff using xdiff
	 *
	 * @param string $fromData from file data
	 * @param string $toData to file data
	 * @param integer $context context lines
	 * @return string diff content
	 */
	private function GetXDiff($fromData, $toData, $context = 3)
	{
		return xdiff_string_diff($fromData, $toData, $context);
	}

	/**
	 * Gets the commit for this filediff
	 *
	 * @return commit object
	 */
	public function GetCommit()
	{
		return $this->GetProject()->GetCommit($this->commitHash);
	}

	/**
	 * Sets the commit for this filediff
	 *
	 * @param mixed $commit commit object
	 */
	public function SetCommit($commit)
	{
		if (!$commit)
			return;

		$this->SetCommitHash($commit->GetHash());
	}

	/**
	 * Sets the hash of the commit for this filediff
	 *
	 * @param string $hash hash
	 */
	public function SetCommitHash($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return;

		$this->commitHash = $hash;
	}
}
