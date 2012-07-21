/*
 * GitPHP Javascript commit graph loader
 * 
 * Initialized script modules used on the commit activity graph
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/commitactivitygraph", "common"], function($, commitActivityGraph) {
	$(function() {
		commitActivityGraph.init('div#graph');
	});
});
