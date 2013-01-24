$(window).ready( function() {

	/* input 배경색변경 */
	$(".faderInput").each(function() {
		$(this).focus( function() {
			$(this).animate({backgroundColor:'#fafafa'}, 400);
		}).blur( function() {			
			$(this).animate({backgroundColor:'#efefef'}, 400);
		});
	});
	$(".errorInput").each(function() {
		$(this).focus( function() {
			$(this).animate({backgroundColor:'#fff6f6'}, 400);
		}).blur( function() {			
			$(this).animate({backgroundColor:'#fbe2e2'}, 400);
		});
	});
	
});