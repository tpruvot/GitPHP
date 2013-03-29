/*
 * GitPHP Javascript commitdiff loader
 * 
 * Initializes script modules used on the commitdiff page
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/sidebyside.tools"],
function($, sbsTools) {
	jQuery(function(){
		var toc = $('div.commitDiffSBS div.SBSTOC');
		var content = $('div.SBSContent');
		if ((toc.size() > 0) && (content.size() > 0)) {
			require(["jquery", "modules/sidebysidecommitdiff"],
				function($, sbsDiff) {
					$(function() {
						sbsDiff.init(toc, content);
					});
				}
			);
		}

		sbsTools.init();
		// global side by side tools
		window.toggleTabs    = function(refElem) { return sbsTools.toggleTabs(refElem); };
		window.toggleNumbers = function(refElem) { return sbsTools.toggleNumbers(refElem); };
		window.toggleLeft    = function(refElem) { return sbsTools.toggleLeft(refElem); };
		window.toggleRight   = function(refElem) { return sbsTools.toggleRight(refElem); };
		window.scrollToDiff  = function(refElem, focusClass) { return sbsTools.scrollToDiff(refElem, focusClass); };
	});
});
