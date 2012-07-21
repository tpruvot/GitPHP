/*
 * GitPHP language distribution graph
 * 
 * Display language distribution graph
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["modules/geturl", "modules/getproject", "d3"],
	function(url, project) {

		var width = 600;
		var height = 600;
		var radius = 200;
		var innerRadius = 100;
		var growRadius = 20;

		var pie = null;
		var color = null;
		var svg = null;

		var arcGroup = null;
		var arc = null;
		var grownArc = null;

		var placeholderGroup = null;
		var placeholder = null;

		var centerGroup = null;
		var langLabel = null;
		var countLabel = null;
		var filesLabel = null;

		var pieTween = function(d, i) {
			var i = d3.interpolate({startAngle: 0, endAngle: 0}, {startAngle: d.startAngle, endAngle: d.endAngle});
			return function(t) {
				return arc(i(t));
			};
		};

		var init = function(graphContainer) {

			pie = d3.layout.pie().value(function(d) {
				return d.value;
			});

			color = d3.scale.category20();

			svg = d3.select(graphContainer).append("svg")
				.attr("width", width)
				.attr("height", height);

			placeholderGroup = svg.append("g")
				.attr("transform", "translate(" + (width/2) + "," + (height/2) + ")");

			arcGroup = svg.append("g")
				.attr("transform", "translate(" + (width/2) + "," + (height/2) + ")");

			centerGroup = svg.append("g")
				.attr("transform", "translate(" + (width/2) + "," + (height/2) + ")");

			langLabel = centerGroup.append("text")
				.attr("dy", -25)
				.attr("font-size", "16")
				.attr("text-anchor", "middle")
				.style('opacity', 0);

			countLabel = centerGroup.append("text")
				.attr("dy", 0)
				.attr("text-anchor", "middle")
				.attr("font-size", "20")
				.text("Loading");

			filesLabel = centerGroup.append("text")
				.attr("dy", 20)
				.attr("text-anchor", "middle")
				.attr("fill", "gray")
				.attr("font-size", "12")
				.text("files");

			placeholder = placeholderGroup.append("path")
				.attr("fill", "#EFEFEF")
				.attr("d", d3.svg.arc().innerRadius(innerRadius).outerRadius(radius).startAngle(0).endAngle(6.28318531)());

			arc = d3.svg.arc().innerRadius(innerRadius).outerRadius(radius);
			grownArc = d3.svg.arc().innerRadius(innerRadius).outerRadius(radius + growRadius);

			d3.json(url + "?p=" + project + "&a=graphdata&g=languagedist", function(data) {
				var dataEntries = d3.entries(data);
				var count = 0;
				if (dataEntries.length > 0) {
					dataEntries.forEach(function(d) {
						count += d.value;
					});
				}
				countLabel.text(count);

				var paths = arcGroup.selectAll("path").data(pie(dataEntries));
				
				paths.enter().append("path")
					.attr("stroke", "white")
					.attr("stroke-width", 0.5)
					.attr("fill", function(d, i) { return color(i); })
					.transition()
					.duration(750)
					.attrTween("d", pieTween)
					.each("end", function() {
						placeholderGroup.remove();
					});

				arcGroup.selectAll("path").on("mouseover", function(d) {
						d3.select(this).transition()
							.duration(250)
							.attr("d", grownArc);
						langLabel.transition().style('opacity', 1).text(d.data.key);
						countLabel.text(d.data.value);
					})
					.on("mouseout", function(d) {
						d3.select(this).transition()
							.duration(250)
							.attr("d", arc);
						langLabel.transition().style('opacity', 0);
						countLabel.text(count);
					});

			});
		};

		return {
			init: init
		};
	}
);
