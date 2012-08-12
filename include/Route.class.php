<?php
/**
 * Route definition
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
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
	 * Constructor
	 *
	 * @param string $path route path
	 * @param string[] $constraints route constraints
	 * @param string[] $extraParameters additional route parameters
	 */
	public function __construct($path, $constraints = array(), $extraParameters = array())
	{
		if (empty($path))
			throw new Exception('Path is required');

		$this->path = $path;
		$this->constraints = $constraints;
		$this->extraParameters = $extraParameters;
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
	 * Test if route matches given parameters
	 *
	 * @param string[] $params parameters
	 * @return boolean true if matech
	 */
	public function Match($params)
	{
		$constraints = $this->GetConstraints();

		foreach ($constraints as $param => $constraint) {
			if (empty($params[$param]))
				return false;
			if (!preg_match('@^' . $constraint . '$@', $params[$param]))
				return false;
		}

		$path = explode('/', $this->GetPath());
		foreach ($path as $pathpiece) {
			if (strncmp($pathpiece, ':', 1) === 0) {
				$param = substr($pathpiece, 1);
				if (empty($params[$param]))
					return false;
			}
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
		if ($this->parent)
			return array_merge($this->parent->GetConstraints(), $this->constraints);

		return $this->constraints;
	}

	/**
	 * Get list of params used in this route
	 *
	 * @return string[] array of parameters
	 */
	public function GetUsedParameters()
	{
		$params = array();
		if ($this->parent)
			$params = $this->parent->GetUsedParameters();

		$path = explode('/', $this->path);
		foreach ($path as $pathpiece) {
			if (strncmp($pathpiece, ':', 1) === 0) {
				$params[] = substr($pathpiece, 1);
			}
		}

		if (count($this->extraParameters) > 0) {
			$params = array_merge($params, array_keys($this->extraParameters));
		}

		return $params;
	}

	/**
	 * Get additional parameters
	 *
	 * @return string[] additional parameters
	 */
	public function GetExtraParameters()
	{
		if ($this->parent)
			return array_merge($this->parent->GetExtraParameters(), $this->extraParameters);

		return $this->extraParameters;
	}

	/**
	 * Set parent route
	 *
	 * @param GitPHP_Route $parent parent route
	 */
	public function SetParent($parent)
	{
		$this->parent = $parent;
	}

}
