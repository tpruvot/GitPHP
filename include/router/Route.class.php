<?php
/**
 * Route definition
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Router
 */
class GitPHP_Route
{
	/**
	 * The route path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The route constraints
	 *
	 * @var string[]
	 */
	protected $constraints = array();

	/**
	 * Additional route parameters
	 *
	 * @var string[]
	 */
	protected $extraParameters = array();

	/**
	 * Parent route
	 *
	 * @var GitPHP_Route
	 */
	protected $parent;

	/**
	 * Cached constraints
	 *
	 * @var array
	 */
	protected $cachedConstraints = null;

	/**
	 * Cached used parameters
	 *
	 * @var array
	 */
	protected $cachedUsedParameters = null;

	/**
	 * Cached extra parameters
	 *
	 * @var array
	 */
	protected $cachedExtraParameters = null;

	/**
	 * Cached url parameters
	 *
	 * @var array
	 */
	protected $cachedUrlParameters = null;

	/**
	 * Constructor
	 *
	 * @param string $path route path
	 * @param string[] $constraints route constraints
	 * @param string[] $extraParameters additional route parameters
	 * @param GitPHP_Route $parent parent route
	 */
	public function __construct($path, $constraints = array(), $extraParameters = array(), $parent = null)
	{
		if (empty($path))
			throw new Exception('Path is required');

		$this->path = $path;
		$this->constraints = $constraints;
		$this->extraParameters = $extraParameters;
		$this->parent = $parent;
	}

	/**
	 * Get route path
	 *
	 * @return string $path
	 */
	public function GetPath()
	{
		if ($this->parent)
			return $this->parent->GetPath() . '/' . $this->path;

		return $this->path;
	}

	/**
	 * Test if this route matches the given path
	 *
	 * @param string $path path
	 * @return array|boolean array of parameters or false if not matched
	 */
	public function Match($path)
	{
		if (empty($path))
			return false;

		$routepieces = explode('/', $this->GetPath());
		$constraints = $this->GetConstraints();
		foreach ($routepieces as $i => $routepiece) {
			if (strncmp($routepiece, ':', 1) === 0) {
				$routepiece = substr($routepiece, 1);
				if (!empty($constraints[$routepiece])) {
					$pattern = '(?P<' . $routepiece . '>' . $constraints[$routepiece] . ')';
				} else {
					$pattern = '(?P<' . $routepiece . '>.+)';
				}
				$routepieces[$i] = $pattern;
			}
		}

		$routepattern = implode('/', $routepieces);

		if (!preg_match('@^' . $routepattern . '$@', $path, $regs))
			return false;

		$params = array();
		foreach ($regs as $key => $register) {
			if (!is_string($key))
				continue;
			$params[$key] = $register;
		}
		$extraparams = $this->GetExtraParameters();
		if (count($extraparams) > 0) {
			foreach ($extraparams as $key => $value) {
				$params[$key] = $value;
			}
		}

		return $params;
	}

	/**
	 * Test if route is valid for the given parameters
	 *
	 * @param string[] $params parameters
	 * @return boolean true if matech
	 */
	public function Valid($params)
	{
		$constraints = $this->GetConstraints();

		foreach ($constraints as $param => $constraint) {
			if (empty($params[$param]))
				return false;
			if (!preg_match('@^' . $constraint . '$@', $params[$param]))
				return false;
		}

		if ($this->cachedUrlParameters === null) {
			$this->cachedUrlParameters = array();
			$path = explode('/', $this->GetPath());
			foreach ($path as $pathpiece) {
				if (strncmp($pathpiece, ':', 1) === 0) {
					$param = substr($pathpiece, 1);
					$this->cachedUrlParameters[] = $param;
				}
			}
		}

		foreach ($this->cachedUrlParameters as $param) {
			if (empty($params[$param]))
				return false;
		}

		return true;
	}

	/**
	 * Build route from parameters
	 *
	 * @param string[] $params parameters
	 * @return string full route
	 */
	public function Build($params)
	{
		$path = $this->GetPath();

		$routepieces = explode('/', $path);
		foreach ($routepieces as $i => $piece) {
			if (strncmp($piece, ':', 1) !== 0) {
				// not a param
				continue;
			}
			$paramname = substr($piece, 1);
			$routepieces[$i] = $params[$paramname];
		}
		
		return trim(implode('/', $routepieces), '/');
	}

	/**
	 * Get constraint patterns
	 *
	 * @return string[] constraints
	 */
	private function GetConstraints()
	{
		if ($this->cachedConstraints === null) {
			if ($this->parent)
				$this->cachedConstraints = array_merge($this->parent->GetConstraints(), $this->constraints);
			else
				$this->cachedConstraints = $this->constraints;
		}

		return $this->cachedConstraints;
	}

	/**
	 * Get list of params used in this route
	 *
	 * @return string[] array of parameters
	 */
	public function GetUsedParameters()
	{
		if ($this->cachedUsedParameters === null) {
			$this->cachedUsedParameters = array();
			if ($this->parent)
				$this->cachedUsedParameters = $this->parent->GetUsedParameters();

			$path = explode('/', $this->path);
			foreach ($path as $pathpiece) {
				if (strncmp($pathpiece, ':', 1) === 0) {
					$this->cachedUsedParameters[] = substr($pathpiece, 1);
				}
			}

			if (count($this->extraParameters) > 0) {
				$this->cachedUsedParameters = array_merge($this->cachedUsedParameters, array_keys($this->extraParameters));
			}
		}

		return $this->cachedUsedParameters;
	}

	/**
	 * Get additional parameters
	 *
	 * @return string[] additional parameters
	 */
	private function GetExtraParameters()
	{
		if ($this->cachedExtraParameters === null) {
			if ($this->parent)
				$this->cachedExtraParameters = array_merge($this->parent->GetExtraParameters(), $this->extraParameters);
			else
				$this->cachedExtraParameters = $this->extraParameters;
		}

		return $this->cachedExtraParameters;
	}

	/**
	 * Compare routes for precedence
	 *
	 * @param GitPHP_Route $a route a
	 * @param GitPHP_Route $b route b
	 */
	public static function CompareRoute($a, $b)
	{
		$apath = $a->GetPath();
		$bpath = $b->GetPath();

		$acount = substr_count($apath, ':');
		$bcount = substr_count($bpath, ':');

		if ($acount == $bcount) {
			$acount2 = substr_count($apath, '/');
			$bcount2 = substr_count($bpath, '/');
			if ($acount2 == $bcount2)
				return 0;

			return $acount2 < $bcount2 ? 1 : -1;
		}

		return $acount < $bcount ? 1 : -1;
	}

}
