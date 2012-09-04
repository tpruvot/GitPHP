/*
 * GitPHP Javascript common loader
 * 
 * Initializes script modules used across all pages
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/getproject", "modules/lang", "modules/tooltip.snapshot", "modules/tooltip.commit", "modules/tooltip.tag"],
	function($, project, lang, tooltipSnapshot, tooltipCommit, tooltipTag) {
		$(function() {
			lang($('div.lang_select'));
			tooltipSnapshot($('a.snapshotTip'));
		});

		if (project) {
			tooltipCommit($('a.commitTip'));
			tooltipTag($('a.tagTip'));
		}
	}
);
