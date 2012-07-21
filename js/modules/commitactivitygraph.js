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

		var x = null;
		var y = null;

		var histogram = null;

		var svg = null;

		var barGroup = null;

		var histogramdata = null;
		var subhistogramdata = null;

		var xaxis = null;
		var yaxis = null;

		var start = null;
		var end = null;

		var redraw = function(mode) {
			if (end == histogramdata.length) {
				subhistogramdata = histogramdata.slice(start);
			} else {
				subhistogramdata = histogramdata.slice(start, end);
			}

			x.domain([subhistogramdata[0].x, subhistogramdata[subhistogramdata.length-1].x]);
			svg.transition().duration(500).select(".xaxis").call(xaxis);

			var bardata = barGroup.selectAll("rect")
				.data(subhistogramdata, function(d) { return d.x.getTime() });

			bardata.enter().append("rect")
				.attr("class", "bar")
				.attr("fill", "steelblue")
				.attr("width", 20)
				.style("opacity", 0)
				.attr("x", function(d) {
					if (mode == 1) {
						return x(d.x) - 60.5;
					} else if (mode == 2) {
						return x(d.x) + 59.5;
					} else {
						return x(d.x) - .5;
					}
				})
				.attr("height", 0);

			bardata.transition().duration(500)
				.attr("x", function(d, i) { return x(d.x) - .5; })
				.attr("height", function(d) { return height - y(d.y); })
				.style("opacity", 1);

			bardata.exit()
				.transition().duration(500)
				.attr("height", 0)
				.attr("x", function(d, i) {
					return x(d.x) - .5;
				})
				.style("opacity", 0)
				.remove();

		};

		var init = function(graphContainer) {

			url = getUrl();
			project = getProject();

			x = d3.time.scale()
				.range([10, width-10]);
			y = d3.scale.linear()
				.range([height, 0]);

			svg = d3.select(graphContainer).append("svg")
				.attr("width", width + 60)
				.attr("height", height + 60);

			barGroup = svg.append("g")
				.attr("transform", "translate(20," + (height+20) + ")scale(1,-1)");

			d3.json(url + "?p=" + project + "&a=graphdata&g=commitactivity", function(data) {
				data.forEach(function(d) {
					d.CommitEpoch = new Date(d.CommitEpoch * 1000);
				});
				var extent = d3.extent(data, function(d) { return d.CommitEpoch; });
				extent[1].setMonth(extent[1].getMonth()+2);

				var range = d3.time.months(extent[0], extent[1]);

				histogram = d3.layout.histogram()
					.value(function(d) {
						return d.CommitEpoch;
					})
					.range(extent)
					.bins(d3.time.months(extent[0], extent[1]));
				histogramdata = histogram(data);

				start = histogramdata.length - 15;
				end = histogramdata.length;

				y.domain([0, d3.max(histogramdata, function(d) { return d.y; })]);

				xaxis = d3.svg.axis()
					.scale(x)
					.ticks(d3.time.months, 1)
					.orient("bottom");

				svg.append("g")
					.attr("class", "xaxis")
					.attr("transform", "translate(30," + (height+20) + ")");

				yaxis = d3.svg.axis()
					.scale(y)
					.ticks(10)
					.orient("right");

				svg.append("g")
					.attr("class", "yaxis")
					.attr("transform", "translate(" + (width+30) + ",20)")
					.call(yaxis);

				redraw();

				d3.select(window).on("keydown", function() {
					if (d3.event.keyCode == 37) {
						if (start > 0) {
							end--;
							start--;
							redraw(1);
						}
					} else if (d3.event.keyCode == 39) {
						if (end < histogramdata.length) {
							end++;
							start++;
							redraw(2);
						}
					}
				});

			});
		};

		return {
			init: init
		};
	}
);
