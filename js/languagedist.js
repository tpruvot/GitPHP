/*
 * GitPHP Javascript language distribution graph loader
 * 
 * Initialized script modules used on the language distribution graph
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/languagedistgraph", "common"], function($, languageDistGraph) {
	$(function() {
		languageDistGraph.init('div#graph');
	});
});
