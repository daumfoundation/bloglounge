
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
				boomImageSet(itemId,direction, $("response message", msg).text());
			} else {
				alert($("response message", msg).text());
			}
		  },
		  error: function(msg) {
			 alert('unknown error');
		  }
		});

	};

	function boomImageSet(itemId, direction, classname) {
		if(typeof classname == "undefined") return false;

		try {
			var bu = $('#boomUp'+itemId);
			var bd = $('#boomDown'+itemId);

			if (direction == 'down') {
				bd.removeClass('isntBoomedDown');		
				bd.removeClass('isBoomedDown');

				bd.addClass(classname);
			} else { // direction = 'up'
				bu.removeClass('isntBoomedUp');			
				bu.removeClass('isBoomedUp');

				bu.addClass(classname);
			}
		} catch (e) { };
	};

	// element Selector
	function sValue(objName) {
		var obj = document.getElementById(objName);
		return obj.options[obj.selectedIndex].value;
	};

	function oValue(objName) {
		var obj = document.getElementById(objName);
		return (obj.checked)?'y':'n';
	};

	// embed code generator
	function getEmbedCode(movie,width,height,id,bg,FlashVars,menu, transparent, quality, bgcolor, allowScriptAccess, version){
		try {
			if(movie == undefined || width == undefined || height == undefined)
				return false;
			
			if ( FlashVars == undefined) {
				var _FlashVars_object = '';
				var _FlashVars_embed = '';
			} else {
				var _FlashVars_object = '<param name="FlashVars" value="'+FlashVars+'" />';
				var _FlashVars_embed = ' FlashVars="'+FlashVars+'" ';
			};
			
			if ( menu == undefined) {
				var _menu_object = '';
				var _menu_embed = '';
			} else {
				var _menu_object = '<param name="menu" value="'+menu+'" />';
				var _menu_embed = ' menu="'+menu+'" ';
			};
			
			if ( transparent == undefined) {
				var _transparent_object = '';
				var _transparent_embed = '';
			} else {
				var _transparent_object = '<param name="wmode" value="'+transparent+'" />';
				var _transparent_embed = ' wmode="'+transparent+'" ';
			};
			
			if ( quality == undefined) {
				var _quality_object = '';
				var _quality_embed = '';
			} else {
				var _quality_object = '<param name="quality" value="'+quality+'" />';
				var _quality_embed = ' quality="'+quality+'" ';
			};
			
			if ( bgcolor == undefined) {
				var _bgcolor_object = '';
				var _bgcolor_embed = '';
			} else {
				var _bgcolor_object = '<param name="bgcolor" value="'+bgcolor+'" />';
				var _bgcolor_embed = ' bgcolor="'+bgcolor+'" ';
			};
			
			if ( allowScriptAccess == undefined) {
				var _allowScriptAccess_object = '';
				var _allowScriptAccess_embed = '';
			} else {
				var _allowScriptAccess_object = '<param name="allowScriptAccess" value="'+allowScriptAccess+'" />';
				var _allowScriptAccess_embed = ' allowScriptAccess="'+allowScriptAccess+'" ';
			};
		
			if  (version == undefined) {
				version = '7,0,0,0';
			};
		
			var flashStr=
			'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version='+version+'" width="'+width+'" height="'+height+'" id="'+id+'" align="middle"><param name="movie" value="'+movie+'" />'+_allowScriptAccess_object+_FlashVars_object+_menu_object+_quality_object+_bgcolor_object+_transparent_object;
			flashStr += '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="'+movie+'"'+' width="'+width+'"'+' height="'+height+'"'+_allowScriptAccess_embed+_FlashVars_embed+_menu_embed+_quality_embed+_bgcolor_embed+_transparent_embed+' />'+
			'</object>';
			
			return flashStr;
		} catch(e) {
			return false;
		};
	};

	function writeCode(str, id) {
			if (id == undefined) document.write(str);
			else document.getElementById(id).innerHTML = str;
	};

	// updateFeed

	function updateFeed() {
		 $.ajax({
		  type: "POST",
		  url: _path +'/service/update/',
		  success: function(msg){
		  },
		  error: function(msg) {
		  }
		});
	};

	// imagePreloader
	function imagePreloader() {
		for (var i=0; i<arguments.length; i++) {
			var img = new Element("img", {"src":arguments[i]});
			img.onload = function() { };
		};
	};
	

	// ncloud Added

	function collectDiv(div1, div2) {
		div1 = $(div1);
		div2 = $(div2);
		if(div1.height() > div2.height()) {
			div2.height( div1.height() );
		} else {
			div1.height( div2.height() );
		}
	}

	function faderInputEffects() {
		$$('.inputText');
		$(document.body).getElements('.faderInput').addEvents(
		{
			'focus' : function() {
				bgFader(this, '#FFFFFF')
			},
			'blur' : function() {
				bgFader(this, '#EFEFEF');
			}
		});
	}

	function join(obj, message) {
	//	location.href = obj.href;
		return true;
	}

	function login(obj, message) {
	//	location.href = obj.href;
		return true;
	}

	function logout(obj, message) {
	//	location.href = obj.href;
		return true;
	}

	function goto(href) {
		location.href = href;
	}
	
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