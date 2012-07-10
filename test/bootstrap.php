<?php

require(dirname(__FILE__) . '/../include/AutoLoader.class.php');
spl_autoload_register(array('GitPHP_AutoLoader', 'AutoLoad'));
