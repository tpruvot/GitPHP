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
define('GITPHP_GITOBJECTDIR', GITPHP_INCLUDEDIR . 'git/');
define('GITPHP_CONTROLLERDIR', GITPHP_INCLUDEDIR . 'controller/');
define('GITPHP_CACHEDIR', GITPHP_INCLUDEDIR . 'cache/');
define('GITPHP_LIBDIR', GITPHP_BASEDIR . 'lib/');
define('GITPHP_SMARTYDIR', GITPHP_LIBDIR . 'smarty/libs/');
define('GITPHP_LOCALEDIR', GITPHP_BASEDIR . 'locale/');

include_once(GITPHP_INCLUDEDIR . 'version.php');

require(GITPHP_INCLUDEDIR . 'AutoLoader.class.php');
spl_autoload_register('GitPHP_AutoLoader::AutoLoad');

date_default_timezone_set('UTC');

include_once(GITPHP_INCLUDEDIR . 'helpers.php');

$router = new GitPHP_Router();

try {

	/*
	 * Use the default language in the config if user has no preference
	 * with en_US as the fallback
	 */
	if (!GitPHP_Resource::Instantiated()) {
		GitPHP_Resource::Instantiate(GitPHP_Config::GetInstance()->GetValue('locale', 'en_US'));
	}

	/*
	 * Debug
	 */
	if (GitPHP_Log::GetInstance()->GetEnabled()) {
		GitPHP_Log::GetInstance()->SetStartTime(GITPHP_START_TIME);
		GitPHP_Log::GetInstance()->SetStartMemory(GITPHP_START_MEM);
	}

	/*
	 * Check for required executables
	 */
	if (!GitPHP_GitExe::GetInstance()->Valid()) {
		throw new GitPHP_MessageException(sprintf(__('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'), GitPHP_GitExe::GetInstance()->GetBinary(), 'gitbin'), true, 500);
	}

	$controller = $router->GetController();
	if ($controller) {
		$controller->Initialize();
		$controller->RenderHeaders();
		$controller->Render();
		unset($controller);
	}

} catch (Exception $e) {

	if (GitPHP_Config::GetInstance()->GetValue('debug', false)) {
		throw $e;
	}

	if (!GitPHP_Resource::Instantiated()) {
		/*
		 * In case an error was thrown before instantiating
		 * the resource manager
		 */
		GitPHP_Resource::Instantiate('en_US');
	}

	$controller = new GitPHP_Controller_Message();
	$controller->SetParam('message', $e->getMessage());
	if ($e instanceof GitPHP_MessageException) {
		$controller->SetParam('error', $e->Error);
		$controller->SetParam('statuscode', $e->StatusCode);
	} else {
		$controller->SetParam('error', true);
	}
	$controller->Initialize();
	$controller->RenderHeaders();
	$controller->Render();

	unset($controller);

}

unset($router);

GitPHP_Log::GetInstance()->Log('MemoryCache count: ' . GitPHP_MemoryCache::GetInstance()->GetCount());

GitPHP_ProjectList::DestroyInstance();
GitPHP_MemoryCache::DestroyInstance();
GitPHP_Config::DestroyInstance();
GitPHP_GitExe::DestroyInstance();

if (GitPHP_Log::GetInstance()->GetEnabled()) {
	$entries = GitPHP_Log::GetInstance()->GetEntries();
	foreach ($entries as $logline) {
		echo "<br />\n" . htmlspecialchars($logline, ENT_QUOTES, 'UTF-8', true);
	}
	unset($logline);
	unset($entries);
}

GitPHP_Log::DestroyInstance();

?>
