$(window).ready( function() {
	if($("#member_id").val()=="") { $("#member_id").focus(); } 
	else { $("#member_password").focus(); }

	/* input 배경색변경 */
	$(".faderInput").each(function() {
		$(this).focus( function() {
			$(this).animate({backgroundColor:'#fafafa'}, 400);
		}).blur( function() {			
			$(this).animate({backgroundColor:'#efefef'}, 400);
		});
	});
});