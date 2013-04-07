<?php

require(dirname(__FILE__) . '/../include/AutoLoader.class.php');
spl_autoload_register(array('GitPHP_AutoLoader', 'AutoLoad'));

define('GITPHP_BASEDIR', dirname(__FILE__) . '/../');
define('GITPHP_CACHEDIR', GITPHP_BASEDIR . 'cache/');
define('GITPHP_CONFIGDIR', GITPHP_BASEDIR . 'config/');

define('GITPHP_COMPRESS_TAR', 'tar');
define('GITPHP_COMPRESS_BZ2', 'tbz2');
define('GITPHP_COMPRESS_GZ', 'tgz');
define('GITPHP_COMPRESS_ZIP', 'zip');

define('GITPHP_TEST_RESOURCES', dirname(__FILE__) . '/resources');
define('GITPHP_TEST_PROJECTROOT', GITPHP_TEST_RESOURCES . '/testprojectroot');
