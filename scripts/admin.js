/* project message */
var messageInterval = 0;
function addMessage(message, type) {
	if(typeof(type) == 'undefined') {
		type = 'information';
	}

	var pm = $('#float_project_message ul');
	var top = $('#float_project_message ul li').length;
	top = top * 28;
	var message = $('<li>').html(message);
	pm.animate({'top':(-top)+'px'},'fast');
	pm.append(message);

	$("#float_project_message").fadeTo('fast',0.8);

	if(messageInterval!=0) {
		clearInterval( messageInterval );
		messageInterval = 0;
	}

	messageInterval = setInterval( function() {
		$("#float_project_message").fadeTo('slow',0.0);
	}, 4000);
}

function collectDiv(div1, div2) {
	div1 = $(div1);
	div2 = $(div2);
	if(div1.height() > div2.height()) {
		div2.height( div1.height() );
	} else {
		div1.height( div2.height() );
	}
}

function collectFloatProjectMessage() {
	pm = $("#float_project_message");
	return pm.css('top', ($(window).height()-28) + $(window).scrollTop() + 'px').css('left', (($(window).width()-pm.width())/2)+'px');
}

$(window).resize( function() {
	collectFloatProjectMessage();
});

$(window).scroll( function() {
	collectFloatProjectMessage();
});

$(window).ready( function() {
	collectFloatProjectMessage().fadeTo('fast',0, function() { $(this).show(); } );

	/* input 배경색변경 */
	$(".faderInput").each(function() {
		$(this).focus( function() {
			$(this).animate({backgroundColor:'#fafafa'}, 400);
		}).blur( function() {			
			$(this).animate({backgroundColor:'#ffffff'}, 400);
		});
	});
});