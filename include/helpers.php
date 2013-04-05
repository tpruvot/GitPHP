<?php

defined('GITPHP_COMPRESS_TAR') || define('GITPHP_COMPRESS_TAR', 'tar');
defined('GITPHP_COMPRESS_BZ2') || define('GITPHP_COMPRESS_BZ2', 'tbz2');
defined('GITPHP_COMPRESS_GZ')  || define('GITPHP_COMPRESS_GZ', 'tgz');
defined('GITPHP_COMPRESS_ZIP') || define('GITPHP_COMPRESS_ZIP', 'zip');

/**
 * translate wrapper function for readability, single string
 *
 * @param string $str string to translate
 * @return string translated string
 */
function __($str)
{
	if (GitPHP_Resource::Instantiated())
		return GitPHP_Resource::GetInstance()->translate($str);
	return $str;
}

/**
 * Gettext wrapper function for readability, plural form
 *
 * @param string $singular singular form of string
 * @param string $plural plural form of string
 * @param int $count number of items
 * @return string translated string
 */
function __n($singular, $plural, $count)
{
	if (GitPHP_Resource::Instantiated())
		return GitPHP_Resource::GetInstance()->ngettext($singular, $plural, $count);
	if ($count > 1)
		return $plural;
	return $singular;
}

/**
 * Log shortcut
 */
function LOGI($line)
{
	GitPHP_DebugLog::GetInstance()->Log($line);
}
