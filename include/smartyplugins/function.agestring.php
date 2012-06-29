<?php
/**
 * Smarty function to turn an age in seconds into a human-readable string
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 *
 * @param array $params parameter array
 * @return string human readable string
 */
function smarty_function_agestring($params, Smarty_Internal_Template $template)
{
	if (empty($params['age'])) {
		trigger_error("agestring: missing 'age' parameter");
		return;
	}

	$age = $params['age'];

	$resource = $template->getTemplateVars('resource');

	$output = null;

	if ($age > 60*60*24*365*2) {

		$years = (int)($age/60/60/24/365);
		$output = sprintf($resource->ngettext('%1$d year ago', '%1$d years ago', $years), $years);

	} else if ($age > 60*60*24*(365/12)*2) {

		$months = (int)($age/60/60/24/(365/12));
		$output = sprintf($resource->ngettext('%1$d month ago', '%1$d months ago', $months), $months);

	} else if ($age > 60*60*24*7*2) {

		$weeks = (int)($age/60/60/24/7);
		$output = sprintf($resource->ngettext('%1$d week ago', '%1$d weeks ago', $weeks), $weeks);

	} else if ($age > 60*60*24*2) {

		$days = (int)($age/60/60/24);
		$output = sprintf($resource->ngettext('%1$d day ago', '%1$d days ago', $days), $days);

	} else if ($age > 60*60*2) {

		$hours = (int)($age/60/60);
		$output = sprintf($resource->ngettext('%1$d hour ago', '%1$d hours ago', $hours), $hours);

	} else if ($age > 60*2) {

		$min = (int)($age/60);
		$output = sprintf($resource->ngettext('%1$d min ago', '%1$d min ago', $min), $min);

	} else if ($age > 2) {

		$sec = (int)$age;
		$output = sprintf($resource->ngettext('%1$d sec ago', '%1$d sec ago', $sec), $sec);

	} else {

		$output = $resource->translate('right now');

	}

	if (!empty($params['assign']))
		$template->assign($params['assign'], $output);
	else
		return $output;
}

?>
