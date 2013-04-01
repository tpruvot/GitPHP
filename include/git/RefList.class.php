<?php
/**
 * Base class representing a list of refs
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_RefList implements Iterator
{
	/**
	 * The project
	 *
	 * @var GitPHP_Project
	 */
	protected $project;

	/**
	 * Whether this list is running in compatbility mode
	 *
	 * @var boolean
	 */
	protected $compat = false;

	/**
	 * The refs
	 *
	 * @var array
	 */
	protected $refs = array();

	/**
	 * Whether data has been loaded
	 *
	 * @var boolean
	 */
	protected $dataLoaded = false;

	/**
	 * Constructor
	 *
	 * @param GitPHP_Project $project project
	 */
	public function __construct($project)
	{
		if (!$project)
			throw new Exception('Project is required');

		$this->project = $project;
	}

	/**
	 * Gets the project
	 *
	 * @return GitPHP_Project project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * Gets whether this list is running in compatibility mode
	 *
	 * @return bool true if compatibilty mode
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * Sets whether this list is running in compatibility mode
	 *
	 * @param bool $compat true if compatibility mode
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;
	}

	/**
	 * Loads data for this ref list
	 */
	protected abstract function LoadData();

	/**
	 * Reads the list of refs using the git executable
	 *
	 * @param string $type ref type
	 * @return array array of refs
	 */
	protected function ReadRefListGit($type)
	{
		if (empty($type))
			return;

		$args = array();
		$args[] = '--' . $type;
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_SHOW_REF, $args);

		$lines = explode("\n", $ret);

		$refs = array();

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/' . $type . '\/([^^]+)(\^{})?$/', $line, $regs)) {
				$refs[$regs[2]] = $regs[1];
			}
		}

		return $refs;
	}

	/**
	 * Reads the list of refs using the raw git files
	 *
	 * @param string $type ref type
	 * @return array array of refs
	 */
	protected function ReadRefListRaw($type)
	{
		if (empty($type))
			return;

		$refs = array();

		$prefix = 'refs/' . $type;
		$fullPath = $this->project->GetPath() . '/' . $prefix;
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
		if (file_exists($this->project->GetPath() . '/packed-refs')) {
			$packedRefs = explode("\n", file_get_contents($this->project->GetPath() . '/packed-refs'));

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

	/**
	 * Get refs in a specific order using git executable
	 *
	 * @param string $type type of ref
	 * @param string $order order to use
	 * @param int $count limit the number of results
	 * @return array array of refs
	 */
	protected function GetOrderedRefsGit($type, $order, $count = 0)
	{
		if (empty($type) || empty($order))
			return null;

		$args = array();
		$args[] = '--sort=' . $order;
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			$args[] = '--count=' . $count;
		}
		$args[] = '--';
		$args[] = 'refs/' . $type;
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->project->GetPath(), GIT_FOR_EACH_REF, $args);

		$lines = explode("\n", $ret);

		$prefix = 'refs/' . $type . '/';
		$prefixLen = strlen($prefix);

		$refs = array();

		foreach ($lines as $ref) {
			$ref = substr($ref, $prefixLen);
			if (!empty($ref))
				$refs[] = $ref;
		}

		return $refs;
	}

	/**
	 * Checks if a ref exists
	 *
	 * @param string $ref ref name
	 * @return boolean true if exists
	 */
	public function Exists($ref)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		return isset($this->refs[$ref]);
	}

	/**
	 * Rewinds the iterator
	 *
	 * @return mixed
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->refs);
	}

	/**
	 * Returns the current ref
	 *
	 * @return mixed
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->refs);
	}

	/**
	 * Returns the current key
	 *
	 * @return mixed
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs);
	}

	/**
	 * Advance the pointer
	 *
	 * @return mixed
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->refs);
	}

	/**
	 * Test for a valid pointer
	 *
	 * @return boolean
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs) !== null;
	}

}
