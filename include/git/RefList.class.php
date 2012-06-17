<?php
/**
 * GitPHP RefList
 *
 * Base class representing a list of refs
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * RefList
 *
 * @package GitPHP
 * @subpackage Git
 * @abstract
 */
abstract class GitPHP_RefList implements Iterator
{
	/**
	 * project
	 *
	 * Stores the project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * compat
	 *
	 * Stores whether this list is running in compatbility mode
	 *
	 * @access protected
	 */
	protected $compat = false;

	/**
	 * refs
	 *
	 * Stores the refs
	 *
	 * @access protected
	 */
	protected $refs = array();

	/**
	 * dataLoaded
	 *
	 * Stores whether data has been loaded
	 *
	 * @access protected
	 */
	protected $dataLoaded = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 */
	public function __construct($project)
	{
		if (!$project)
			throw new Exception('Project is required');

		$this->project = $project;
	}

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * GetCompat
	 *
	 * Gets whether this list is running in compatibility mode
	 *
	 * @access public
	 * @return bool true if compatibilty mode
	 */
	public function GetCompat()
	{
		return $this->compat;
	}

	/**
	 * SetCompat
	 *
	 * Sets whether this list is running in compatibility mode
	 *
	 * @access public
	 * @param bool $compat true if compatibility mode
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;
	}

	/**
	 * LoadData
	 *
	 * Loads data for this ref list
	 * 
	 * @access protected
	 * @abstract
	 */
	protected abstract function LoadData();

	/**
	 * ReadRefListGit
	 *
	 * Reads the list of refs using the git executable
	 *
	 * @access protected
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
	 * ReadRefListRaw
	 *
	 * Reads the list of refs using the raw git files
	 *
	 * @access protected
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
			$head = substr($refFiles[$i], $fullPathLen);
			
			if (empty($head) || isset($refs[$head]))
				continue;

			$hash = trim(file_get_contents($refFiles[$i]));
			if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
				$refs[$head] = $hash;
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
	 * GetOrderedRefsGit
	 *
	 * Get refs in a specific order using git executable
	 *
	 * @access public
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
	 * Exists
	 *
	 * Checks if a ref exists
	 *
	 * @access public
	 * @return boolean true if exists
	 */
	public function Exists($ref)
	{
		if (!$this->dataLoaded)
			$this->LoadData();

		return isset($this->refs[$ref]);
	}

	/**
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return reset($this->refs);
	}

	/**
	 * current
	 *
	 * Returns the current revision
	 */
	function current()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return current($this->refs);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs);
	}

	/**
	 * next
	 *
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return next($this->refs);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataLoaded) {
			$this->LoadData();
		}

		return key($this->refs) !== null;
	}

}
