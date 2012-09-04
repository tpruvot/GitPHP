/*
 * GitPHP Javascript commit tooltip
 * 
 * Displays commit messages in a tooltip
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/geturl", "modules/getproject", 'modules/resources'],
	function($, url, project, resources) {

		function getCommitHash(element) {
			var hash = element.attr('href').match(/h=([0-9a-fA-F]{4,40}|HEAD)/);
			if (!hash) {
				hash = element.attr('href').match(/\/commits\/([0-9a-fA-F]{4,40}|HEAD)/);
			}
			return hash ? hash[1] : null;
		}

		function buildTipConfig(hash) {
			return {
				content: {
					text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + resources.Loading + '" />',
					ajax: {
						url: url,
						data: {
							p: project,
							a: 'commit',
							o: 'jstip',
							h: hash
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
						var hash = getCommitHash(jThis);
						if (!hash) {
							return;
						}
						var config = buildTipConfig(hash);
						jThis.qtip(config);
					});
				});
			}
		}
	}
);
