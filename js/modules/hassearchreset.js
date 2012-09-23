define(['modernizr'], function() {
	var tested = false;

	return function() {
		if (!tested) {
			Modernizr.testStyles(
				'#modernizr, x::-webkit-search-cancel-button { width: 9px; }',
				function (elem, rule) {
					Modernizr.addTest('searchreset', elem.offsetWidth == 9);
				}
			);
			tested = true;
		}

		return Modernizr.searchreset;
	};
});
