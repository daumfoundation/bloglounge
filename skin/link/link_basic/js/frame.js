$(window).ready( function() {
	var frame = $("#frameWrap");
	var toolbar = $("#toolbar");

	setInterval(function() {
		frame.height( $(window).height() - toolbar.height());
	}, 100);
});