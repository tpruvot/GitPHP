<?php
/**
 * GitPHP
 *
 * Index
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Define start time / memory for benchmarking
 */
define('GITPHP_START_TIME', microtime(true));
define('GITPHP_START_MEM', memory_get_usage());

/**
 * Define some paths
 */
define('GITPHP_BASEDIR', dirname(__FILE__) . '/');
define('GITPHP_CONFIGDIR', GITPHP_BASEDIR . 'config/');
define('GITPHP_INCLUDEDIR', GITPHP_BASEDIR . 'include/');
define('GITPHP_LOCALEDIR', GITPHP_BASEDIR . 'locale/');
define('GITPHP_CACHEDIR', GITPHP_BASEDIR . 'cache/');
define('GITPHP_LIBDIR', GITPHP_BASEDIR . 'lib/');
define('GITPHP_SMARTYDIR', GITPHP_LIBDIR . 'smarty/libs/');
define('GITPHP_GESHIDIR', GITPHP_LIBDIR . 'geshi/');

define('GITPHP_COMPRESS_TAR', 'tar');
define('GITPHP_COMPRESS_BZ2', 'tbz2');
define('GITPHP_COMPRESS_GZ', 'tgz');
define('GITPHP_COMPRESS_ZIP', 'zip');

/**
 * Low level setup
 */
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding("UTF-8");
}
date_default_timezone_set('UTC');

/* strlen() can be overloaded in mbstring extension, so always using mb_orig_strlen for binary data */
if (!function_exists('mb_orig_strlen')) {
	function mb_orig_strlen($str)
	{
		return strlen($str);
	}
}

if (!function_exists('mb_orig_substr')) {
	function mb_orig_substr($str, $offset, $len = null)
	{
		return isset($len) ? substr($str, $offset, $len) : substr($str, $offset);
	}
}

/**
 * Version header
 */
include(GITPHP_INCLUDEDIR . 'version.php');

/**
 * Autoload setup
 */
require(GITPHP_INCLUDEDIR . 'AutoLoader.class.php');
spl_autoload_register(array('GitPHP_AutoLoader', 'AutoLoad'));


$router = new GitPHP_Router();

try {

	$controller = $router->GetController();
	if ($controller) {
		$controller->Initialize();
		$controller->RenderHeaders();
		$controller->Render();
	}

} catch (Exception $e) {

	$messageController = $router->GetMessageController();
	$messageController->Initialize();

	if (!($e instanceof GitPHP_MessageException)) {
		$config = $messageController->GetConfig();
		if ($config && $config->GetValue('debug')) {
			throw $e;
		}
	}

	$messageController->SetParam('exception', $e);
	$messageController->RenderHeaders();
	$messageController->Render();

	unset($messageController);

}

unset($router);

?>
