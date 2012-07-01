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
 * @param Smarty_Internal_Template $template smarty template
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
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d year ago', '%1$d years ago', $years), $years);
		else
			$output = sprintf($years == 1 ? '%1$d year ago' : '%1$d years ago', $years);

	} else if ($age > 60*60*24*(365/12)*2) {

		$months = (int)($age/60/60/24/(365/12));
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d month ago', '%1$d months ago', $months), $months);
		else
			$output = sprintf($months == 1 ? '%1$d month ago' : '%1$d months ago', $months);

	} else if ($age > 60*60*24*7*2) {

		$weeks = (int)($age/60/60/24/7);
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d week ago', '%1$d weeks ago', $weeks), $weeks);
		else
			$output = sprintf($weeks == 1 ? '%1$d week ago' : '%1$d weeks ago', $weeks);

	} else if ($age > 60*60*24*2) {

		$days = (int)($age/60/60/24);
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d day ago', '%1$d days ago', $days), $days);
		else
			$output = sprintf($days == 1 ? '%1$d day ago' : '%1$d days ago', $days);

	} else if ($age > 60*60*2) {

		$hours = (int)($age/60/60);
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d hour ago', '%1$d hours ago', $hours), $hours);
		else
			$output = sprintf($hours == 1 ? '%1$d hour ago' : '%1$d hours ago', $hours);

	} else if ($age > 60*2) {

		$min = (int)($age/60);
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d min ago', '%1$d min ago', $min), $min);
		else
			$output = sprintf($min == 1 ? '%1$d min ago' : '%1$d min ago', $min);

	} else if ($age > 2) {

		$sec = (int)$age;
		if ($resource)
			$output = sprintf($resource->ngettext('%1$d sec ago', '%1$d sec ago', $sec), $sec);
		else
			$output = sprintf($sec == 1 ? '%1$d sec ago' : '%1$d sec ago', $sec);

	} else {

		if ($resource)
			$output = $resource->translate('right now');
		else
			$output = 'right now';

	}

	if (!empty($params['assign']))
		$template->assign($params['assign'], $output);
	else
		return $output;
}

?>
