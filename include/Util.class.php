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
	 * @param boolean $filesystem true if this is a filesystem path (to also check for backslash for windows paths)
	 * @return string path with a trailing slash
	 */
	public static function AddSlash($path, $filesystem = true)
	{
		if (empty($path))
			return $path;

		$end = substr($path, -1);

		if (!(( ($end == '/') || ($end == ':')) || ($filesystem && GitPHP_Util::IsWindows() && ($end == '\\')))) {
			if (GitPHP_Util::IsWindows() && $filesystem) {
				$path .= '\\';
			} else {
				$path .= '/';
			}
		}

		return $path;
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

	public static function NullFile()
	{
		return self::IsWindows() ? 'NUL' : '/dev/null';
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
	 * @param string $str string to slugify
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
	 * Get the filename of a given path
	 *
	 * Based on Drupal's basename
	 *
	 * @param string $path path
	 * @param string $suffix optionally trim this suffix
	 * @return string filename
	 */
	public static function BaseName($path, $suffix = null)
	{
		$sep = '/';
		if (GitPHP_Util::IsWindows()) {
			$sep .= '\\';
		}

		$path = rtrim($path, $sep);

		if (!preg_match('@[^' . preg_quote($sep) . ']+$@', $path, $matches)) {
			return '';
		}

		$filename = $matches[0];

		if ($suffix) {
			$filename = preg_replace('@' . preg_quote($suffix, '@') . '$@', '', $filename);
		}
		return $filename;
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
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (($file == '.') || ($file == '..')) {
					continue;
				}
				$fullFile = $dir . '/' . $file;
				if (is_dir($fullFile)) {
					$subFiles = GitPHP_Util::ListDir($fullFile);
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
		if (GitPHP_Util::IsWindows())
			$baseurl = rtrim($baseurl, "\\");
		return rtrim($baseurl, "/");
	}

	/**
	 * Tests whether a function is allowed to be called
	 *
	 * @param string $function functio name
	 * @return true if allowed
	 */
	public static function FunctionAllowed($function)
	{
		if (empty($function))
			return false;

		$disabled = @ini_get('disable_functions');
		if (!$disabled) {
			// no disabled functions
			// or ini_get is disabled so we can't reliably figure this out
			return true;
		}

		$disabledlist = explode(', ', $disabled);
		return !in_array($function, $disabledlist);
	}

}
