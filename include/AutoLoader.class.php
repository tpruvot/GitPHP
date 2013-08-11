<?php
/**
 * GitPHP AutoLoader (backport)
 *
 * Class to handle autoloading other classes
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 */
class GitPHP_AutoLoader
{
	/**
	 * Autoload a class
	 *
	 * @param string $classname class name
	 */
	public static function AutoLoad($classname)
	{
		$filename = GitPHP_AutoLoader::ClassFilename($classname);

		if (empty($filename))
			return;

		$path = __DIR__ . '/' . $filename;

		if (is_readable($path))
			require($path);
	}

	/**
	 * Gets the path a class
	 *
	 * @param string $classname class name
	 * @return string path
	 */
	public static function ClassFilename($classname)
	{
		if (empty($classname))
			return null;

		if (strncmp($classname, 'GitPHP_', 7) !== 0)
			return null;

		$classname = substr($classname, 7);

		$path = '';
		if (strncmp($classname, 'Controller', 10) === 0) {
			$path = 'controller/';
		} else if (strncmp($classname, 'ProjectList', 11) === 0) {
			$path = 'git/projectlist/';
		} else if (strncmp($classname, 'FileMimeType', 12) === 0) {
			$path = 'git/filemimetype/';
		} else if (($classname == 'Blob') || (strncmp($classname, 'BlobLoad', 8) === 0)) {
			$path = 'git/blob/';
		} else if (($classname == 'Tag') || (strncmp($classname, 'TagLoad', 7) === 0)) {
			$path = 'git/tag/';
		} else if (strncmp($classname, 'Archive', 7) === 0) {
			$path = 'git/archive/';
		} else if ((strlen($classname) > 9) && (substr_compare($classname, 'Exception', -9, 9) === 0)) {
			$path = 'exception/';
		} else if (strpos($classname, 'Cache') !== false) {
			$path = 'cache/';
		} else if (strncmp($classname, 'Route', 5) === 0) {
			$path = 'router/';
		} else if (in_array($classname, array(
				'Config',
				'DebugLog',
				'Mime',
				'Resource',
				'Util'
			))) {
			$path = '';
		} else {
			$path = 'git/';
		}

		if ((strlen($classname) > 10) && (substr_compare($classname, '_Interface', -10, 10) === 0)) {
			$classname = substr($classname, 0, -10);
			$path .= $classname . '.interface.php';
		} else {
			$path .= $classname . '.class.php';
		}

		return $path;
	}

}
