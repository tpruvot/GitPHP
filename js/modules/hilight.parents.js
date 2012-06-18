/*
 * GitPHP Javascript parents hilight
 * 
 * Hilight commit's parents in short log history
 * 
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Javascript
 */
define(["jquery"],
	function($) {

		function splitParentHashes(jEl) {
			var hashes = jEl.attr('title').trim();
			jEl.removeAttr('title');
			return hashes.split(' ');
		}

		return function(elements) {
			elements.each(function(){

				var jThis = $(this);
				var jTR = jThis.parents('tr:first');
				if (jTR.size()) {

					var jTable = jTR.parents('table.shortlog:first');
					var arHashes = splitParentHashes(jTR);
					var arParents = [];
					for (var phash in arHashes) {
						var hash = arHashes[phash];
						jTD = jTable.find("td.hash:contains('" + hash + "')");
						arParents.push(jTD.parents('tr:first').first());
					}
					jTR.data('parents', arParents);

					// only hilight merge commits (2 or 3 parents)
					if (arParents.length > 1) {
						jTR.mouseenter(function() {
							var arParents = jQuery(this).data('parents');
							if (arParents) for (var tr in arParents) {
								jQuery(arParents[tr]).addClass('hoverParent');
							}
						})
						.mouseleave(function() {
							var arParents = jQuery(this).data('parents');
							if (arParents) for (var tr in arParents) {
								jQuery(arParents[tr]).removeClass('hoverParent');
							}
						});
					}
				}
			});
		}
	}
);
