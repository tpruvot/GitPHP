<?php
/**
 * Constants for git commands
 */
define('GIT_CAT_FILE','cat-file');
define('GIT_DIFF_TREE','diff-tree');
define('GIT_LS_TREE','ls-tree');
define('GIT_REV_LIST','rev-list');
define('GIT_REV_PARSE','rev-parse');
define('GIT_SHOW_REF','show-ref');
define('GIT_ARCHIVE','archive');
define('GIT_GREP','grep');
define('GIT_BLAME','blame');
define('GIT_NAME_REV','name-rev');
define('GIT_FOR_EACH_REF','for-each-ref');
define('GIT_CONFIG','config');

/**
 * Class to wrap git executable
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_GitExe implements GitPHP_Observable_Interface
{

	/**
	 * Stores the singleton instance
	 */
	protected static $instance;

	/**
	 * Stores the binary path internally
	 */
	protected $binary;
	
	/**
	 * Stores the binary version internally
	 */
	protected $version;

	/**
	 * Stores whether the version has been read
	 */
	protected $versionRead = false;

	/**
	 * Prefix to set terminal LANG charset.
	 */
	protected $charset = 'en_US.utf-8';

	/**
	 * Observers
	 *
	 * @var GitPHP_Observer_Interface[]
	 */
	protected $observers = array();

	/**
	 * Returns the singleton instance
	 *
	 * @return mixed instance of git exe classe
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'));
			self::$instance->AddObserver(GitPHP_DebugLog::GetInstance());
		}
		return self::$instance;
	}

	/**
	 * Releases the singleton instance
	 */
	public static function DestroyInstance()
	{
		self::$instance = null;
	}

	/**
	 * Constructor
	 *
	 * @param string $binary path to git binary
	 */
	public function __construct($binary = '')
	{
		if (empty($binary))
			$binary = GitPHP_Config::GetInstance()->GetValue('gitbin');

		if (empty($binary)) {
			$binary = GitPHP_GitExe::DefaultBinary();
		}
		$this->binary = escapeshellarg(GitPHP_Util::CleanPath($binary));
	}

	/**
	 * Executes a command
	 *
	 * @param string $projectPath path to project
	 * @param string $command the command to execute
	 * @param string[] $args arguments
	 * @return string full executable string
	 */
	public function Execute($projectPath, $command, $args)
	{
		$fullCommand = $this->CreateCommand($projectPath, $command, $args);

		$this->Log('Begin executing "' . $fullCommand . '"');

		$ret = shell_exec($fullCommand);

		if ($command != GIT_DIFF_TREE) /* reduce noisy results */
			$this->Log('Finish executing "' . $fullCommand . '"' .
				"\nwith result: " . $ret);

		return $ret;
	}

	/**
	 * Opens a resource to a command
	 *
	 * @param string $projectPath path to project
	 * @param string $command the command to execute
	 * @param array $args arguments
	 * @return resource process handle
	 */
	public function Open($projectPath, $command, $args, $mode = 'r')
	{
		$fullCommand = $this->CreateCommand($projectPath, $command, $args);

		return popen($fullCommand, $mode);
	}

	/**
	 * Creates a command
	 *
	 * @param string $projectPath path to project
	 * @param string $command the command to execute
	 * @param array $args arguments
	 * @return string result of command
	 */
	protected function CreateCommand($projectPath, $command, $args)
	{
		$gitDir = '';
		if (!empty($projectPath)) {
			$gitDir = '--git-dir=' . escapeshellarg(GitPHP_Util::CleanPath($projectPath));
		}

		$command = $this->binary . ' ' . $gitDir . ' ' . $command . ' ' . implode(' ', $args);

		if (!empty($this->charset) && !GitPHP_Util::IsWindows()) {
			$command = 'LANG='.$this->charset . ' ' . $command;
		}

		return $command;
	}

	/**
	 * Gets the binary for this executable
	 *
	 * @return string binary
	 */
	public function GetBinary()
	{
		return $this->binary;
	}

	/**
	 * Gets the version of the git binary
	 *
	 * @return string version
	 */
	public function GetVersion()
	{
		if (!$this->versionRead)
			$this->ReadVersion();

		return $this->version;
	}

	/**
	 * Reads the git version
	 */
	protected function ReadVersion()
	{
		$this->versionRead = true;

		$this->version = '';

		$versionCommand = $this->binary . ' --version';
		$ret = trim(shell_exec($versionCommand));
		if (preg_match('/^git version ([0-9\.]+)$/i', $ret, $regs)) {
			$this->version = $regs[1];
		}
	}

	/**
	 * Tests if this version of git can skip through the revision list
	 *
	 * @return boolean true if we can skip
	 */
	public function CanSkip()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/* Skip only appears in git >= 1.5.0 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tests if this version of git can show the size of a blob when listing a tree
	 *
	 * @return true if we can show sizes
	 */
	public function CanShowSizeInTree()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/*
			 * ls-tree -l only appears in git 1.5.3
			 * (technically 1.5.3-rc0 but i'm not getting that fancy)
			 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Tests if this version of git has the regexp tuning option to ignore regexp case
	 *
	 * @return true if we can ignore regexp case
	 */
	public function CanIgnoreRegexpCase()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/*
			 * regexp-ignore-case only appears in git 1.5.3
			 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tests if this executable is valid
	 *
	 * @return boolean true if valid
	 */
	public function Valid()
	{
		if (empty($this->binary))
			return false;

		$code = 0;
		exec($this->binary . ' --version', $tmp, $code);

		return $code == 0;
	}

	/**
	 * Add a new observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function AddObserver($observer)
	{
		if (!$observer)
			return;

		if (array_search($observer, $this->observers) !== false)
			return;

		$this->observers[] = $observer;
	}

	/**
	 * Remove an observer
	 *
	 * @param GitPHP_Observer_Interface $observer observer
	 */
	public function RemoveObserver($observer)
	{
		if (!$observer)
			return;

		$key = array_search($observer, $this->observers);

		if ($key === false)
			return;

		unset($this->observers[$key]);
	}

	/**
	 * Log an execution
	 *
	 * @param string $message message
	 */
	private function Log($message)
	{
		if (empty($message))
			return;

		foreach ($this->observers as $observer) {
			$observer->ObjectChanged($this, GitPHP_Observer_Interface::LoggableChange, array($message));
		}
	}

	/**
	 * Gets the default binary for the platform
	 *
	 * @return string binary
	 */
	public static function DefaultBinary()
	{
		if (GitPHP_Util::IsWindows()) {

			// windows
			if (GitPHP_Util::Is64Bit()) {
				// match x86_64 and x64 (64 bit)
				// C:\Program Files (x86)\Git\bin\git.exe
				$bin = 'C:\\Progra~2\\Git\\bin\\git.exe';
			} else {
				// 32 bit
				// C:\Program Files\Git\bin\git.exe
				$bin = 'C:\\Progra~1\\Git\\bin\\git.exe';
			}
			if (!is_file($bin)) {
				// use PATH
				$bin = 'git.exe';
			}
			return $bin;
		} else {
			// *nix, just use PATH
			return 'git';
		}
	}

}
