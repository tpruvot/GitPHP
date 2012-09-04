/*
 * GitPHP Javascript snapshot tooltip
 * 
 * Displays choices of snapshot format in a tooltip
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", 'modules/snapshotformats', 'modules/resources'],
	function($, formats, resources) {
		
		function buildTipContent(href) {
			var content = '<div>' + resources.Snapshot + ': ';
			var first = true;
			var cleanurl = href.indexOf('/snapshot') != -1;
			for (var type in formats) {
				if (formats.hasOwnProperty(type)) {
					if (!first) {
						content += ' | ';
					}
					if (cleanurl) {
						var newhref = href.replace("/snapshot", "/" + type);
						content += '<a href="' + newhref + '">' + formats[type] + '</a>';
					} else {
						content += '<a href="' + href + '&fmt=' + type + '">' + formats[type] + '</a>';
					}
					first = false;
				}
			}
			content += '</div>';
			return content;
		}

		function buildTipConfig(content) {
			return {
				content: {
					text: content
				},
				show: {
					event: 'click'
				},
				hide: {
					fixed: true,
					delay: 150
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
						var href = jThis.attr('href');
						var content = buildTipContent(href);
						var config = buildTipConfig(content);
						jThis.qtip(config);
						jThis.click(function() { return false; });
					});
				});
			}
		}
	}
);
