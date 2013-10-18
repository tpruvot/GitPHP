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

define(["jquery", "migrate", "modules/getproject", "modules/lang", "modules/tooltip.snapshot"],
	function(jQuery, migrate, project, lang, tooltipSnapshot) {

		$ = jQuery;

		lang($('div.lang_select'));
		tooltipSnapshot($('a.snapshotTip'));

		if (project) {

			require(["jquery", "modules/tooltip.commit", "modules/tooltip.tag", "modules/hilight.parents"],
				function($, tooltipCommit, tooltipTag, highlightParents) {
					$(function() {
						highlightParents($('table.shortlog td.hash'));
						tooltipCommit($('a.commitTip'));
						tooltipTag($('a.tagTip'));
					});
				}
			);

		}
	}
);
