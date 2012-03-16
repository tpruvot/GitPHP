/*
 * GitPHP Javascript parents tooltip
 * 
 * Hilight parents in short log history
 * 
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery"],
	function($) {

		function splitParentHashes(jEl) {
			var hashes = jEl.attr('commit-parents').trim();
			return hashes.split(' ');
		}

		return function(elements) {
			elements.each(function(){

				var jThis = $(this);
				var jTR = jThis.parents('tr[commit-parents]:first');
				if (jTR.size()) {
					var jTable = jTR.parents('table:first');
					var hashes = splitParentHashes(jTR);
					var tds = [];
					for (var phash in hashes) {
						var hash = hashes[phash];
						jTD = jQuery("td.monospace:contains('" + hash + "')");
						tds.push(jTD.first());
					}
					jTR.data('parents', tds);

					jTR.mouseenter(function() {
						if (jQuery(this).data('parents')) {
							var tds = jQuery(this).data('parents');
							for (var td in tds)
								jQuery(tds[td]).css('background-color', '#edece6');
						}
					})
					.mouseleave(function() {
						if (jQuery(this).data('parents')) {
							var tds = jQuery(this).data('parents');
							for (var td in tds)
								jQuery(tds[td]).css('background-color', '');
						}
					});
				}
			});
		}
	}
);
