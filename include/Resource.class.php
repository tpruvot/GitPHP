<?php

require_once(GITPHP_BASEDIR . 'lib/php-gettext/streams.php');
require_once(GITPHP_BASEDIR . 'lib/php-gettext/gettext.php');

/**
 * Resource string manager class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Resource
{

	/**
	 * Constant of the locale cookie in the user's browser
	 *
	 * @var string
	 */
	const LocaleCookie = 'GitPHPLocale';

	/**
	 * Locale cookie lifetime
	 *
	 * @var int
	 */
	const LocaleCookieLifetime = 31536000;	// 1 year
	
	/**
	 * Stores the currently instantiated locale identifier
	 *
	 * @var string
	 */
	protected $locale = '';

	/**
	 * Stores the current locale reader
	 *
	 * @var gettext_reader
	 */
	protected $localeReader;

	/**
	 * Constructor
	 *
	 * @param string $locale locale to instantiate
	 * @param boolean $cache true to cache strings
	 */
	public function __construct($locale, $cache = true)
	{
		if (!(($locale == 'en_US') || ($locale == 'en'))) {

			if (!is_readable(GITPHP_LOCALEDIR . $locale . '/gitphp.mo'))
				throw new Exception('Invalid locale');

			$reader = new FileReader(GITPHP_LOCALEDIR . $locale . '/gitphp.mo');
			if (!$reader) {
				throw new Exception('Invalid locale');
			}

			$this->localeReader = new gettext_reader($reader, $cache);
		}
		$this->locale = $locale;
	}

	/**
	 * Translate a string
	 *
	 * @param string $string string
	 */
	public function translate($string)
	{
		if (!$this->localeReader)
			return $string;

		return $this->localeReader->translate($string);
	}

	/**
	 * Translate a pluralized string
	 *
	 * @param string $singular singular form
	 * @param string $plural plural form
	 * @param int $count count
	 */
	public function ngettext($singular, $plural, $count)
	{
		if (!$this->localeReader)
			return $count == 1 ? $singular : $plural;

		return $this->localeReader->ngettext($singular, $plural, $count);
	}

	/**
	 * Gets the currently instantiated locale
	 *
	 * @return string locale identifier
	 */
	public function GetLocale()
	{
		return $this->locale;
	}

	/**
	 * Gets the currently instantiated primary locale
	 *
	 * @return string primary locale identifier
	 */
	public function GetPrimaryLocale()
	{
		$locale = $this->locale;
		$underscore = strpos($locale, '_');
		if ($underscore !== false) {
			$locale = substr($locale, 0, $underscore);
		}
		return $locale;
	}

	/**
	 * Gets the current instantiated locale's name
	 *
	 * @return string locale name
	 */
	public function GetLocaleName()
	{
		if (!$this->localeReader)
			return 'English';

		$localeName = $this->localeReader->translate('English');

		if ($localeName == 'English') {
			// someone didn't translate the language name - don't mislabel it as english
			return '';
		}

		return $localeName;
	}

	/**
	 * Gets the list of supported locales and their languages
	 *
	 * @param boolean $includeNames true to include native names of languages
	 * @return string[] array of locales mapped to languages
	 */
	public static function SupportedLocales($includeNames = true)
	{
		$locales = array();

		if ($includeNames)
			$locales['en_US'] = 'English';
		else
			$locales[] = 'en_US';

		if ($dh = opendir(GITPHP_LOCALEDIR)) {
			while (($file = readdir($dh)) !== false) {
				$fullPath = GITPHP_LOCALEDIR . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath) && is_file($fullPath . '/gitphp.mo')) {
					if ($includeNames) {
						$resource = new GitPHP_Resource($file, false);
						$locales[$file] = $resource->GetLocaleName();
					} else {
						$locales[] = $file;
					}
				}
			}
		}

		if ($includeNames)
			ksort($locales);
		
		return $locales;
	}

	/**
	 * Given a list of preferred locales, try to find a matching supported locale
	 *
	 * @param string $httpAcceptLang HTTP Accept-Language string
	 * @return string matching locale if found
	 */
	public static function FindPreferredLocale($httpAcceptLang)
	{
		if (empty($httpAcceptLang))
			return '';

		$locales = explode(',', $httpAcceptLang);

		$localePref = array();

		foreach ($locales as $locale) {
			$quality = '1.0';
			$localeData = explode(';', trim($locale));
			if (count($localeData) > 1) {
				$q = trim($localeData[1]);
				if (substr($q, 0, 2) == 'q=') {
					$quality = substr($q, 2);
				}
			}
			$localePref[$quality][] = trim($localeData[0]);
		}
		krsort($localePref);

		$supportedLocales = GitPHP_Resource::SupportedLocales(false);

		foreach ($localePref as $quality => $qualityArray) {
			foreach ($qualityArray as $browserLocale) {
				$locale = str_replace('-', '_', $browserLocale);
				$loclen = strlen($locale);

				foreach ($supportedLocales as $lang) {
					/* 
					 * using strncasecmp with length of the preferred
					 * locale means we can match both full
					 * language + country preference specifications
					 * (en_US) as well as just language specifications
					 * (en)
					 */
					if (strncasecmp($locale, $lang, $loclen) === 0) {
						return $lang;
					}
				}
			}
		}
		return '';
	}

}

