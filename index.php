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
 * Use utf-8 encoding
 */
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding("UTF-8");
}

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

/**
 * Low level setup
 */
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding("UTF-8");
}
date_default_timezone_set('UTC');

/**
 * Version header
 */
include(GITPHP_INCLUDEDIR . 'version.php');

/**
 * Autoload setup
 */
require(GITPHP_INCLUDEDIR . 'AutoLoader.class.php');
spl_autoload_register('GitPHP_AutoLoader::AutoLoad');

/**
 * Compatibility global functions / defines
 */
include(GITPHP_INCLUDEDIR . 'helpers.php');

$router = new GitPHP_Router();

try {
	$controller = $router->GetController();
	if ($controller) {
		$controller->Initialize();
		$controller->RenderHeaders();
		$controller->Render();
		unset($controller);
	}

} catch (Exception $e) {

	$messageController = $router->GetMessageController();
	$messageController->Initialize();

	$messageController->SetParam('message', $e->getMessage());
	if ($e instanceof GitPHP_MessageException) {
		$messageController->SetParam('error', $e->Error);
		$messageController->SetParam('statuscode', $e->StatusCode);
	} else {
		$config = $messageController->GetConfig();
		if ($config && $config->GetValue('debug')) {
			throw $e;
		}
	}

	$messageController->SetParam('exception', $e);
	try {
		$messageController->RenderHeaders();
		$messageController->Render();
	} catch (Exception $e) {
		echo('<b>Fatal error</b> : '.$e->getMessage().'<pre style="font-size: 8pt;">');
		if (GitPHP_Config::GetInstance()->GetValue('debug')) {
			$stack = $e->getTrace();
			$error = @ error_get_last(); // PHP 5.2
			if (!empty($error))
				echo 'Last error at '.str_replace(GITPHP_BASEDIR,'',$error['file']).':'.
					$error['line'].': '.$error['message'];
			else
				foreach ($stack as $t) print_r($t);
		}
		die;
	}

	unset($messageController);

}

unset($router);

GitPHP_ProjectList::DestroyInstance();
GitPHP_MemoryCache::DestroyInstance();
GitPHP_Config::DestroyInstance();
GitPHP_GitExe::DestroyInstance();
GitPHP_Log::DestroyInstance();

?>
