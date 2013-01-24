$(window).ready( function() {
	/* input 배경색변경 */
	$(".faderInput").each(function() {
		$(this).focus( function() {
			$(this).animate({backgroundColor:'#fefefe'}, 400);
		}).blur( function() {			
			$(this).animate({backgroundColor:'#efefef'}, 400);
		});
	});
});