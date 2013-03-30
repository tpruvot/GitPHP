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
	 * Url parameters
	 *
	 * @var string[]
	 */
	protected $urlParameters = array();

	/**
	 * Used parameters
	 *
	 * @var string[]
	 */
	protected $usedParameters = array();

	/**
	 * Cached constraints
	 *
	 * @var array[]
	 */
	protected $cachedConstraints;

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

		// initialize path
		if ($parent)
			$this->path = $parent->GetPath() . '/' . $path;
		else
			$this->path = $path;

		// initialize constraints
		if ($parent)
			$this->constraints = array_merge($parent->constraints, $constraints);
		else
			$this->constraints = $constraints;

		// initialise extra parameters
		if ($parent)
			$this->extraParameters = array_merge($parent->extraParameters, $extraParameters);
		else
			$this->extraParameters = $extraParameters;

		// initialize url parameters
		$fullPath = explode('/', $this->path);
		foreach ($fullPath as $pathpiece) {
			if (strncmp($pathpiece, ':', 1) === 0) {
				$param = substr($pathpiece, 1);
				$this->urlParameters[] = $param;
			}
		}

		// initialize used parameters
		$this->usedParameters = array_merge($this->urlParameters, array_keys($extraParameters));
		if ($parent)
			$this->usedParameters = array_merge($parent->GetUsedParameters(), $this->usedParameters);
		$this->usedParameters = array_unique($this->usedParameters);
	}

	/**
	 * Get route path
	 *
	 * @return string $path
	 */
	public function GetPath()
	{
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
		foreach ($routepieces as $i => $routepiece) {
			if (strncmp($routepiece, ':', 1) === 0) {
				$routepiece = substr($routepiece, 1);
				if (!empty($this->constraints[$routepiece])) {
					$pattern = '(?P<' . $routepiece . '>' . $this->constraints[$routepiece] . ')';
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
		if (count($this->extraParameters) > 0) {
			$params = array_merge($params, $this->extraParameters);
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
		foreach ($this->constraints as $param => $constraint) {
			if (empty($params[$param]))
				return false;
			$paramval = $params[$param];
			if (!isset($this->cachedConstraints[$param][$paramval])) {
				$this->cachedConstraints[$param][$paramval] = preg_match('@^' . $constraint . '$@', $params[$param]);
			}
			if (!$this->cachedConstraints[$param][$paramval])
				return false;
		}

		foreach ($this->urlParameters as $param) {
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
	 * Get list of params used in this route
	 *
	 * @return string[] array of parameters
	 */
	public function GetUsedParameters()
	{
		return $this->usedParameters;
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
