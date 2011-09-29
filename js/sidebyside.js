/*
 * GitPHP javascript sidebyside
 *
 * Javascript Controls for source navigation
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Javascript
 */

/* TODO: use new format to declare js functions...
define(["jquery", "modules/sidebyside", "common"], function($, sbsDocumentReady) {
 ...
}
*/

var scrollElem;

function sbs_toggleTabs(refElem) {
	var el = jQuery(refElem);
	dv = el.parents('div.diff-file').find('.scrollPanel:first');
	var code = dv.find('span.line');
	
	code.each(function() {
		var line=jQuery(this);

		var reg=new RegExp("(\t)", "g");
		var str = line.html();
		if (str.indexOf('class="tab"') > 0) {
			str = line.text();
		} else {
			str = str.replace(reg,'<span class="tab">$1</span>');
		}

		line.html(str);
	});
}

//Hide Line numbers
function sbs_toggleNumbers(refElem) {
	var el = jQuery(refElem);
	dv = el.parents('div.diff-file').find('.scrollPanel:first');
	var nums = dv.find('td.ln');
	nums.toggleClass('hidden');
	if (nums.first().hasClass('hidden')) {
		 //hidden then hide (display:none) is faster
		nums.hide();
	} else {
		nums.show();
	}
}

//Keep only Left Side for Copy/Paste
function sbs_toggleLeft(refElem) {
	var el = jQuery(refElem);
	dv = el.parents('div.diff-file').find('.scrollPanel:first');
	var nums = dv.find('td.ln');
	var col  = dv.find('td.diff-left');
	if (nums.first().hasClass('hidden')) {
		//show all
		dv.find('td').removeClass('hidden').show();
		nums.removeClass('hidden').show();
	} else {
		dv.find('td').addClass('hidden').hide();
		nums.addClass('hidden').hide();
		col.removeClass('hidden').show();
	}
}
//Keep only Right Side for Copy/Paste
function sbs_toggleRight(refElem) {
	var el = jQuery(refElem);
	dv = el.parents('div.diff-file').find('.scrollPanel:first');
	var nums = dv.find('td.ln');
	var col  = dv.find('td.diff-right');
	if (nums.first().hasClass('hidden')) {
		//show all
		dv.find('td').removeClass('hidden').show();
		nums.removeClass('hidden').show();
	} else {
		dv.find('td').addClass('hidden').hide();
		nums.addClass('hidden').hide();
		col.removeClass('hidden').show();
	}
}

/*
 * Scroll to diff in a ScrollPanel (not used yet)
 *
 * refElem    source html control (for multiple panels)
 * focusClass target css class to focus ex: tr.diff-focus:last
 *
 * @return boolean;
 */
function sbs_scrollToDiff(refElem, focusClass) {
	var el = jQuery(refElem);
	dv = el.parents('.diff-file').find('.scrollPanel:first');
	var diff = dv.find(focusClass);
	if (diff.length) {

		var elDest = diff.first();
		var stickToTop = !(focusClass.indexOf('last') > 0);

		var hash = elDest.find('a').attr('name');

		if (stickToTop) {
			// try to get previous
			if (elDest.prev('tr').length)
				elDest = elDest.prev('tr');
		} else {
			// ... or next code line
			if (elDest.next('tr').length)
				elDest = elDest.next('tr');
		}

		var targetOffset = elDest.offset().top;
		var decalY = 200;
		if (!stickToTop) {
			decalY = 400;
		}

		if (el.prop('href')) {
			//manual call
			$(scrollElem).animate({'scrollTop': (targetOffset - decalY)}, 400, function() {
			//	location.hash = hash;
			});
		} else {
			//init call
			$(scrollElem).scrollTop(targetOffset - decalY);
			//location.hash = hash;
		}
	}
}

// use the first element that is "scrollable"
function scrollableElement(els) {

	for (var i = 0, argLength = arguments.length; i < argLength; i++) {
		var el = arguments[i],
		elem = $(el);
		if (elem.scrollTop() > 0) {
			return el;
		} else {
			elem.scrollTop(1);
			var isScrollable = (elem.scrollTop() > 0);
			elem.scrollTop(0);
			if (isScrollable) {
				return el;
			}
		}
	}
	return [];
}

jQuery(document).ready(function() {

	var sbsTOC = $('div.commitDiffSBS div.SBSTOC');
	if (sbsTOC.size() == 0) {
		//only resize height in blobdiff view

		var h = Math.max(window.innerHeight - 250, 500);
		jQuery('.scrollPanel')
			.css('max-height', h.toString() + 'px')
			.css('overflow', 'auto');
	}

	var hash = window.location.hash.replace(/#/,'');
	if (hash.length) {
		//if we have a #hash in url, like Line or diff number
		//let the navigator do his work
		return;
	}

	// Scroll to first diff in sidebyside file.
	var el = jQuery('.scrollPanel');
	el.each(function() {
		var dv=jQuery(this).find('.diff-focus:first');
		scrollElem = scrollableElement('.scrollPanel','html', 'body'); 
		sbs_scrollToDiff(dv,'.diff-focus:first');
		//only first...
		return false;
	});

});

