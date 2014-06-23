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
		return preg_replace('@[/\\\\]+@','/',$path);
	}

	/**
	 * Get the filename of a given path (without .ext)
	 *
	 * @param string $path path
	 * @param string $suffix optionally trim this suffix
	 * @return string filename
	 */
	public static function BaseName($path, $suffix = null)
	{
		$filename = self::CleanPath($path);

		/* wanted ??
		if ($suffix != null) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (strlen($ext)) {
				$suffix = '.'.$ext;
			}
		}
		*/

		return basename($filename, $suffix);
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

	/**
	 * Returns the same timezone label on Windows and Linux
	 *
	 * @param string $tz
	 * @return string real timezone
	 */
	public static function UnifiedTimezone($tz)
	{
		/* This array includes Windows time zone data (from zoneinfo)
		 * @author Ross Smith <pear@smithii.com>
		 */
		$tzdatawin = array(
			'Dateline Standard Time'            => 'Etc/GMT+12',                # (GMT-12:00) Eniwetok, Kwajalein   Dateline Daylight Time
			'Samoa Standard Time'               => 'Pacific/Samoa',             # (GMT-11:00) Midway Island, Samoa  Samoa Daylight Time
			'Hawaiian Standard Time'            => 'HST',                       # (GMT-10:00) Hawaii    Hawaiian Daylight Time
			'Alaskan Standard Time'             => 'AST',                       # (GMT-09:00) Alaska    Alaskan Daylight Time
			'Pacific Standard Time'             => 'PST',                       # (GMT-08:00) Pacific Time (US & Canada); Tijuana   Pacific Daylight Time
			'Mountain Standard Time'            => 'MST',                       # (GMT-07:00) Mountain Time (US & Canada)   Mountain Daylight Time
			'US Mountain Standard Time'         => 'US/Mountain',               # (GMT-07:00) Arizona   US Mountain Daylight Time
			'Canada Central Standard Time'      => 'Canada/Central',            # (GMT-06:00) Saskatchewan  Canada Central Daylight Time
			'Mexico Standard Time'              => 'Mexico/General',            # (GMT-06:00) Mexico City   Mexico Daylight Time
			'Central Standard Time'             => 'CST',                       # (GMT-06:00) Central Time (US & Canada)    Central Daylight Time
			'Central America Standard Time'     => 'CST',                       # (GMT-06:00) Central America   Central America Daylight Time
			'US Eastern Standard Time'          => 'EST',                       # (GMT-05:00) Indiana (East)    US Eastern Daylight Time
			'Eastern Standard Time'             => 'EST',                       # (GMT-05:00) Eastern Time (US & Canada)    Eastern Daylight Time
			'SA Pacific Standard Time'          => 'EST',                       # (GMT-05:00) Bogota, Lima, Quito   SA Pacific Daylight Time
			'Pacific SA Standard Time'          => 'America/Anguilla',          # (GMT-04:00) Santiago  Pacific SA Daylight Time
			'SA Western Standard Time'          => 'America/Anguilla',          # (GMT-04:00) Caracas, La Paz   SA Western Daylight Time
			'Atlantic Standard Time'            => 'America/Anguilla',          # (GMT-04:00) Atlantic Time (Canada)    Atlantic Daylight Time
			'Newfoundland Standard Time'        => 'America/St_Johns',          # (GMT-03:30) Newfoundland  Newfoundland Daylight Time
			'Greenland Standard Time'           => 'America/Godthab',           # (GMT-03:00) Greenland Greenland Daylight Time
			'SA Eastern Standard Time'          => 'America/Araguaina',         # (GMT-03:00) Buenos Aires, Georgetown  SA Eastern Daylight Time
			'E. South America Standard Time'    => 'America/Araguaina',         # (GMT-03:00) Brasilia  E. South America Daylight Time
			'Mid-Atlantic Standard Time'        => 'Atlantic/South_Georgia',    # (GMT-02:00) Mid-Atlantic  Mid-Atlantic Daylight Time
			'Cape Verde Standard Time'          => 'Atlantic/Cape_Verde',       # (GMT-01:00) Cape Verde Is.    Cape Verde Daylight Time
			'Azores Standard Time'              => 'Atlantic/Azores',           # (GMT-01:00) Azores    Azores Daylight Time
			'Greenwich Standard Time'           => 'GMT',                       # (GMT+00:00) Casablanca, Monrovia  Greenwich Daylight Time
			'GMT Standard Time'                 => 'GMT',                       # (GMT+00:00) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London   GMT Daylight Time
			'W. Europe Standard Time'           => 'ECT',                       # (GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna  W. Europe Daylight Time
			'Central Europe Standard Time'      => 'ECT',                       # (GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague Central Europe Daylight Time
			'Romance Standard Time'             => 'ECT',                       # (GMT+01:00) Brussels, Copenhagen, Madrid, Paris   Romance Daylight Time
			'Central European Standard Time'    => 'ECT',                       # (GMT+01:00) Sarajevo, Skopje, Sofija, Vilnius, Warsaw, Zagreb Central European Daylight Time
			'W. Central Africa Standard Time'   => 'ECT',                       # (GMT+01:00) West Central Africa   W. Central Africa Daylight Time
			'GTB Standard Time'                 => 'ART',                       # (GMT+02:00) Athens, Istanbul, Minsk   GTB Daylight Time
			'E. Europe Standard Time'           => 'EET',                       # (GMT+02:00) Bucharest E. Europe Daylight Time
			'Egypt Standard Time'               => 'Egypt',                     # (GMT+02:00) Cairo Egypt Daylight Time
			'South Africa Standard Time'        => 'Africa/Johannesburg',       # (GMT+02:00) Harare, Pretoria  South Africa Daylight Time
			'FLE Standard Time'                 => 'ART',                       # (GMT+02:00) Helsinki, Riga, Tallinn   FLE Daylight Time
			'Jerusalem Standard Time'           => 'Israel',                    # (GMT+02:00) Jerusalem Jerusalem Daylight Time
			'Arabic Standard Time'              => 'Asia/Aden',                 # (GMT+03:00) Baghdad   Arabic Daylight Time
			'Arab Standard Time'                => 'Asia/Riyadh',               # (GMT+03:00) Kuwait, Riyadh    Arab Daylight Time
			'Russian Standard Time'             => 'Europe/Moscow',             # (GMT+03:00) Moscow, St. Petersburg, Volgograd Russian Daylight Time
			'E. Africa Standard Time'           => 'EAT',                       # (GMT+03:00) Nairobi   E. Africa Daylight Time
			'Iran Standard Time'                => 'Asia/Tehran',               # (GMT+03:30) Tehran    Iran Daylight Time
			'Arabian Standard Time'             => 'Asia/Dubai',                # (GMT+04:00) Abu Dhabi, Muscat Arabian Daylight Time
			'Caucasus Standard Time'            => 'Asia/Baku',                 # (GMT+04:00) Baku, Tbilisi, Yerevan    Caucasus Daylight Time
			'Afghanistan Standard Time'         => 'Asia/Kabul',                # (GMT+04:30) Kabul Afghanistan Daylight Time
			'Ekaterinburg Standard Time'        => 'Asia/Yekaterinburg',        # (GMT+05:00) Ekaterinburg  Ekaterinburg Daylight Time
			'West Asia Standard Time'           => 'PLT',                       # (GMT+05:00) Islamabad, Karachi, Tashkent  West Asia Daylight Time
			'India Standard Time'               => 'IST',                       # (GMT+05:30) Calcutta, Chennai, Mumbai, New Delhi  India Daylight Time
			'Nepal Standard Time'               => 'Asia/Katmandu',             # (GMT+05:45) Kathmandu Nepal Daylight Time
			'N. Central Asia Standard Time'     => 'Asia/Novosibirsk',          # (GMT+06:00) Almaty, Novosibirsk   N. Central Asia Daylight Time
			'Central Asia Standard Time'        => 'Asia/Dacca',                # (GMT+06:00) Astana, Dhaka Central Asia Daylight Time
			'Sri Lanka Standard Time'           => 'Asia/Colombo',              # (GMT+06:00) Sri Jayawardenepura   Sri Lanka Daylight Time
			'Myanmar Standard Time'             => 'Asia/Rangoon',              # (GMT+06:30) Rangoon   Myanmar Daylight Time
			'SE Asia Standard Time'             => 'Asia/Bangkok',              # (GMT+07:00) Bangkok, Hanoi, Jakarta   SE Asia Daylight Time
			'North Asia Standard Time'          => 'Asia/Krasnoyarsk',          # (GMT+07:00) Krasnoyarsk   North Asia Daylight Time
			'China Standard Time'               => 'Asia/Chongqing',            # (GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi China Daylight Time
			'North Asia East Standard Time'     => 'Asia/Irkutsk',              # (GMT+08:00) Irkutsk, Ulaan Bataar North Asia East Daylight Time
			'Malay Peninsula Standard Time'     => 'Asia/Kuala_Lumpur',         # (GMT+08:00) Kuala Lumpur, Singapore   Malay Peninsula Daylight Time
			'W. Australia Standard Time'        => 'Australia/Perth',           # (GMT+08:00) Perth W. Australia Daylight Time
			'Taipei Standard Time'              => 'Asia/Taipei',               # (GMT+08:00) Taipei    Taipei Daylight Time
			'Tokyo Standard Time'               => 'Asia/Tokyo',                # (GMT+09:00) Osaka, Sapporo, Tokyo Tokyo Daylight Time
			'Korea Standard Time'               => 'Asia/Seoul',                # (GMT+09:00) Seoul Korea Daylight Time
			'Yakutsk Standard Time'             => 'Asia/Yakutsk',              # (GMT+09:00) Yakutsk   Yakutsk Daylight Time
			'Cen. Australia Standard Time'      => 'Australia/Adelaide',        # (GMT+09:30) Adelaide  Cen. Australia Daylight Time
			'AUS Central Standard Time'         => 'Australia/Darwin',          # (GMT+09:30) Darwin    AUS Central Daylight Time
			'E. Australia Standard Time'        => 'Australia/Brisbane',        # (GMT+10:00) Brisbane  E. Australia Daylight Time
			'AUS Eastern Standard Time'         => 'Australia/Sydney',          # (GMT+10:00) Canberra, Melbourne, Sydney   AUS Eastern Daylight Time
			'West Pacific Standard Time'        => 'Pacific/Guam',              # (GMT+10:00) Guam, Port Moresby    West Pacific Daylight Time
			'Tasmania Standard Time'            => 'Australia/Hobart',          # (GMT+10:00) Hobart    Tasmania Daylight Time
			'Vladivostok Standard Time'         => 'Asia/Vladivostok',          # (GMT+10:00) Vladivostok   Vladivostok Daylight Time
			'Central Pacific Standard Time'     => 'Pacific/Noumea',            # (GMT+11:00) Magadan, Solomon Is., New Caledonia   Central Pacific Daylight Time
			'New Zealand Standard Time'         => 'NST',                       # (GMT+12:00) Auckland, Wellington  New Zealand Daylight Time
			'Fiji Standard Time'                => 'Pacific/Fiji',              # (GMT+12:00) Fiji, Kamchatka, Marshall Is. Fiji Daylight Time
			'Tonga Standard Time'               => 'Pacific/Tongatapu',         # (GMT+13:00) Nuku'alofa    Tonga Daylight Time
		);

		if (self::IsWindows() && array_key_exists($tz, $tzdatawin))
			return $tzdatawin[$tz];

		return $tz;
	}
}
