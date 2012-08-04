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
	$url = null;
	$escape = false;
	if (empty($params['url'])) {
		if (!empty($params['fullurl']) && ($params['fullurl'] == true))
			$url = $template->getTemplateVars('fullscripturl');
		else
			$url = $template->getTemplateVars('scripturl');

		if (empty($url)) {
			trigger_error("geturl: missing url");
			return;
		}
	} else {
		$url = $params['url'];
		unset($params['url']);
	}

	unset($params['fullurl']);

	if (!empty($params['escape']) && ($params['escape'] == true))
		$escape = true;
	unset($params['escape']);

	$router = $template->getTemplateVars('router');
	if (!$router) {
		$clean = $template->getTemplateVars('cleanurl');
		$abbreviate = $template->getTemplateVars('abbreviateurl');
		$router = new GitPHP_Router($clean, $abbreviate);
	}
	$fullurl = $router->GetUrl($url, $params);
	if ($escape)
		$fullurl = htmlspecialchars($fullurl);

	return $fullurl;
}
