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

include(GITPHP_INCLUDEDIR . 'version.php');

require(GITPHP_INCLUDEDIR . 'AutoLoader.class.php');

spl_autoload_register(array('GitPHP_AutoLoader', 'AutoLoad'));

date_default_timezone_set('UTC');

try {

	$controller = GitPHP_Router::GetController();
	if ($controller) {
		$controller->Initialize();
		$controller->RenderHeaders();
		$controller->Render();
	}

} catch (Exception $e) {

	$messageController = new GitPHP_Controller_Message();
	$messageController->Initialize();

	$config = $messageController->GetConfig();
	if ($config && $config->GetValue('debug') && !($e instanceof GitPHP_MessageException)) {
		throw $e;
	}

	$messageController->SetParam('exception', $e);
	$messageController->RenderHeaders();
	$messageController->Render();

	unset($messageController);

}

if (isset($controller)) {
	$log = $controller->GetLog();
	if ($log && $log->GetEnabled()) {
		$entries = $log->GetEntries();
		foreach ($entries as $logline) {
			echo "<br />\n" . htmlspecialchars($logline, ENT_QUOTES, 'UTF-8', true);
		}
		unset($logline);
		unset($entries);
	}
	unset($controller);
}

?>
