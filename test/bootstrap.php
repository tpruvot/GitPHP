<?php

require(dirname(__FILE__) . '/../include/AutoLoader.class.php');
spl_autoload_register(array('GitPHP_AutoLoader', 'AutoLoad'));

define('GITPHP_BASEDIR', dirname(__FILE__) . '/../');
define('GITPHP_CACHEDIR', GITPHP_BASEDIR . 'cache/');

define('GITPHP_TEST_RESOURCES', dirname(__FILE__) . '/resources');
define('GITPHP_TEST_PROJECTROOT', GITPHP_TEST_RESOURCES . '/testprojectroot');
