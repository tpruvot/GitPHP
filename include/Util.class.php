<?php
/**
 * Utility function class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Util
{

	/**
	 * Adds a trailing slash to a directory path if necessary
	 *
	 * @param string $path path to add slash to
	 * @param $filesystem true if this is a filesystem path (to also check for backslash for windows paths)
	 * @return string path with a trailing slash
	 */
	public static function AddSlash($path, $filesystem = true)
	{
		if (empty($path))
			return $path;

		$end = substr($path, -1);

		if (!(( ($end == '/') || ($end == ':')) || ($filesystem && GitPHP_Util::IsWindows() && ($end == '\\')))) {
			//if (GitPHP_Util::IsWindows() && $filesystem) {
			//	$path .= '\\';
			//} else {
				$path .= '/';
			//}
		}

		return self::CleanPath($path);
	}

	/**
	 * Special escape url function to keep slashes in urls...
	 *
	 * @param string $path
	 * @return string path encoded
	 */
	public static function UrlEncodeFilePath($path)
	{
		if (empty($path))
			return $path;

		$encoded = rawurlencode($path);
		$encoded = str_replace('%2F', '/', $encoded);
		return $encoded;
	}

	/**
	 * Tests if this is running on windows
	 *
	 * @return bool true if on windows
	 */
	public static function IsWindows()
	{
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
	}

	/**
	 * Tests if this is a 64 bit machine
	 *
	 * @return bool true if on 64 bit
	 */
	public static function Is64Bit()
	{
		return (strpos(php_uname('m'), '64') !== false);
	}

	/**
	 * Turn a string into a filename-friendly slug
	 *
	 * @return string slug
	 */
	public static function MakeSlug($str)
	{
		$from = array(
			'/'
		);
		$to = array(
			'-'
		);
		return str_replace($from, $to, $str);
	}

	/**
	 * Uniformize separators in a path
	 * @param string $path
	 * @return string
	 */
	public static function CleanPath($path)
	{
		// windows also supports also the "/" separator, but should not mix both
		return preg_replace('@[\\\\]+@','/',$path);
	}

	/**
	 * Get the filename of a given path
	 *
	 * @param string $path path
	 * @param string $suffix optionally trim this suffix
	 * @return string filename
	 */
	public static function BaseName($path, $suffix = null)
	{
		$filename = self::CleanPath($path);

		return basename($filename);
	}

	/**
	 * Provides a geshi language for a given filename
	 *
	 * @param string $filename file name
	 * @return string language
	 */
	public static function GeshiFilenameToLanguage($filename)
	{
		if (strncasecmp($filename, 'Makefile', 8) === 0) {
			return 'make';
		}

		return null;
	}

	/**
	 * Recurses into a directory and lists files inside
	 *
	 * @param string $dir directory
	 * @return string[] array of filenames
	 */
	public static function ListDir($dir)
	{
		$files = array();
		if (self::IsDir($dir) && $dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (($file == '.') || ($file == '..')) {
					continue;
				}
				$fullFile = $dir . '/' . $file;
				if (self::IsDir($fullFile)) {
					$subFiles = self::ListDir($fullFile);
					if (count($subFiles) > 0) {
						$files = array_merge($files, $subFiles);
					}
				} else {
					$files[] = $fullFile;
				}
			}
		}
		return $files;
	}

	/**
	 * Custom is_dir function, return true if a link point to a directory
	 *
	 * @param string
	 * @return boolean
	 */
	public static function IsDir($path) {
		$dir = rtrim(self::CleanPath($path),'/');
		return is_dir($dir) || (is_link($dir) && is_dir("$dir/."));
	}

	/**
	 * Get the base install url (without index)
	 *
	 * @param boolean $full true to return full url (include protocol and hostname)
	 * @return string base url
	 */
	public static function BaseUrl($full = false)
	{
		$baseurl = $_SERVER['SCRIPT_NAME'];
		if (substr_compare($baseurl, 'index.php', -9) === 0)
			$baseurl = dirname($baseurl);
		if ($full) {
			$baseurl = $_SERVER['HTTP_HOST'] . $baseurl;
			if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
				$baseurl = 'https://' . $baseurl;
			else
				$baseurl = 'http://' . $baseurl;
		}
		return rtrim(self::CleanPath($baseurl), "/");
	}
}
