/*
 * GitPHP commit activity graph
 * 
 * Display commit activity history graph
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["modules/geturl", "modules/getproject", "d3"],
	function(getUrl, getProject) {

		var url = null;
		var project = null;

		var width = 960;
		var height = 500;

		var init = function(graphContainer) {

			url = getUrl();
			project = getProject();

			d3.json(url + "?p=" + project + "&a=graphdata&g=commitactivity", function(data) {

			});
		};

		return {
			init: init
		};
	}
);
