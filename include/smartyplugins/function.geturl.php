<?php
/**
 * Smarty function to wrap url builder
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 *
 * @param array $params parameter array
 * @param Smarty_Internal_Template $template smarty template
 * @return string url
 */
function smarty_function_geturl($params, Smarty_Internal_Template $template)
{
	$full = false;
	if (!empty($params['fullurl']) && ($params['fullurl'] == true)) {
		$full = true;
	}
	unset($params['fullurl']);

	$escape = false;
	if (!empty($params['escape']) && ($params['escape'] == true))
		$escape = true;
	unset($params['escape']);

	$router = $template->getTemplateVars('router');
	if (!$router) {
		trigger_error("geturl: missing router");
		return;
	}
	$finalurl = $router->GetUrl($params, $full);
	if ($escape)
		$finalurl = htmlspecialchars($finalurl);

	return $finalurl;
}
