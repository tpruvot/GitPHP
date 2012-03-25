/*
 * GitPHP Javascript blobdiff loader
 * 
 * Initializes script modules used on the blob page
 * 
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/sidebyside.tools", "common"],
function($, sbsTools, common) {
	sbsTools.init();
	// global side by side tools
	window.toggleTabs    = function(refElem) { return sbsTools.toggleTabs(refElem); };
	window.toggleNumbers = function(refElem) { return sbsTools.toggleNumbers(refElem); };
	window.toggleLeft    = function(refElem) { return sbsTools.toggleLeft(refElem); };
	window.toggleRight   = function(refElem) { return sbsTools.toggleRight(refElem); };
	window.scrollToDiff  = function(refElem, focusClass) { return sbsTools.scrollToDiff(refElem, focusClass); };
});
