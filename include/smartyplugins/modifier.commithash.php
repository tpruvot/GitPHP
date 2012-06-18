<?php
/**
 * Commit hashes
 *
 * Modifier to parse git commit hashes in commit messages
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Smarty
 */

require('function.scripturl.php');

/**
 * commithash smarty modifier
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

	$smarty = null;
	$script = smarty_function_scripturl(null, $smarty);
	$script .= '?p='.$projName;

	$pattern = '/\\b([0-9a-f]{7,40})\\b/i';
	$link = $script.'&a=commit&h=${1}';

	if (preg_match($pattern, $text)) {

		$fullLink = '<a class="commithash" href="' . $link . '">${1}</a>';

		return preg_replace($pattern, $fullLink, $text);
	}

	return $text;
}
