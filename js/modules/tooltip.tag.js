/*
 * GitPHP Javascript tag tooltip
 * 
 * Displays tag messages in a tooltip
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/geturl", "modules/getproject", 'modules/resources'],
	function($, url, project, resources) {

		function getTagName(element) {
			var tag = element.attr('href').match(/t=([^&]+)/);
			if (!tag) {
				tag = element.attr('href').match(/\/tags\/([^\/\?]+)/);
			}
			return tag ? tag[1] : null;
		}

		function buildTipConfig(tag) {
			return {
				content: {
					text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + resources.Loading + '" />',
					ajax: {
						url: url,
						data: {
							p: project,
							a: 'tag',
							o: 'jstip',
							t: tag
						},
						type: 'GET'
					}
				},
				style: {
					classes: 'ui-tooltip-gitphp ui-tooltip-light ui-tooltip-shadow'
				},
				position: {
					viewport: $(window)
				}
			}
		}

		return function(elements) {

			if (elements && (elements.size() > 0)) {
				require(['qtip'], function() {
					elements.each(function(){
						var jThis = $(this);
						var tag = getTagName(jThis);
						if (!tag) {
							return;
						}
						var config = buildTipConfig(tag);
						jThis.qtip(config);
					});
				});
			}
		}
	}
);
