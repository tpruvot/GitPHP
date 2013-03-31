<?php
/**
 * Smarty function to get the full url of the current script
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 */
function smarty_function_scripturl($params, &$smarty)
{
	if (GitPHP_Config::GetInstance()->HasKey('self')) {
		$selfurl = GitPHP_Config::GetInstance()->GetValue('self');
		if (!empty($selfurl)) {
			if (substr($selfurl, -4) != '.php') {
				$selfurl = GitPHP_Util::AddSlash($selfurl);
			}
			return $selfurl;
		}
	}

	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
		$scriptstr = 'https://';
	else
		$scriptstr = 'http://';

	// HTTP_HOST is taken directly from the Host: request header (with port, if not 80)
	$scriptstr .= $_SERVER['HTTP_HOST'] . str_replace('/index.php','/',$_SERVER['PHP_SELF']);

	return $scriptstr;
}

?>
