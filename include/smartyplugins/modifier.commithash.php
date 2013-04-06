<?php
/**
 * Modifier to parse git commit hashes in commit messages
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Smarty
 *
 * @param string $text text to find bug references in
 * @param string $project name used in url
 * @return string text with bug references linked
 */
function smarty_modifier_commithash($text, $projName = '')
{
	if (empty($text))
		return $text;

	// default to current project
	if (empty($projName) && isset($_REQUEST['p'])) {
		$projName = $_REQUEST['p'];
	}

	// $vars = Smarty::$global_tpl_vars;
	// $script = $vars['SCRIPT_NAME']->value;

	$link = '?p='.$projName.'&a=commit&h=${1}';

	$pattern = '/\\b([0-9a-f]{7,40})\\b/';

	if (preg_match($pattern, $text, $regs)) {

		$fullLink = '<a class="commithash" href="' . $link . '">${1}</a>';

		$atag = preg_replace($pattern, $fullLink, $text);

		// abbreviate
		$atag = str_replace($regs[1].'</a>', substr($regs[1],0,7).'</a>', $atag);

		return $atag;
	}

	return $text;
}
