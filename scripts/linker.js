
	//
	// common.javascript actions 
	// copyright(c) 2007 laziel, <http://laziel.com>, License under GPL
	//

	// boomUp, boomDown or cancel it

	function boom(itemId, direction) {
		if (!itemId || !direction || ((direction != 'up') && (direction != 'down'))) {
			return false;
		}
		$.ajax({
		  type: "POST",
		  url: _path +'/service/boom/',
		  data: 'itemId=' + itemId + '&direction=' + direction,
		  dataType: 'xml',
		  success: function(msg){		
			error = $("response error", msg).text();
			if(error == "0") {
				boomImageSet(itemId,direction, $("response message", msg).text(), $("response boom_count", msg).text());
			} else {
				alert($("response message", msg).text());
			}
		  },
		  error: function(msg) {
			 alert('unknown error');
		  }
		});

	};

	function boomImageSet(itemId, direction, classname, boomCount) {
		if(typeof classname == "undefined") return false;

		try {
			var bu = $('#boomUp'+itemId);
			var bd = $('#boomDown'+itemId);
			var bc = $('#boomCount'+itemId);

			if (direction == 'down') {
				bd.removeClass('isntBoomedDown');		
				bd.removeClass('isBoomedDown');

				bd.addClass(classname);
			} else { // direction = 'up'
				bu.removeClass('isntBoomedUp');			
				bu.removeClass('isBoomedUp');

				bu.addClass(classname);
			}

			bc.text( boomCount);
		} catch (e) { };
	};

	
	function join(obj, message) {
		if(confirm(message)) {
			return true;
		}

		return false;
	}

	function login(obj, message) {
		if(confirm(message)) {
			return true;		}

		return false;
	}

	function logout(obj, message) {
		if(confirm(message)) {
			return true;
		}

		return false;
	}

	$(window).ready( function() {
	});