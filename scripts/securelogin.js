// Secure Service Layer (not Secure Socket Layer) script for Wing!
// copyright(c) 2007 laziel, <http://www.laziel.com>
var secureLogin=new Abstract({publicKey:null,set:function(){	$.ajax({
		  type: "POST",
		  url: _path +'/service/login/key/',
		  dataType: 'xml',
		  success: function(msg){
			secureLogin.publicKey = $("response publicKey", msg).text();
			if($('#waitoverlay')!==false){
				$('#waitoverlay').class("display","none")
			}
		  },
		  error: function(msg) {
			 alert('unknown error');
		  }
		});},check:function(){if(this.publicKey!=null)return true;this.set();return!(this.publicKey==null)},send:function(){var hash=new Encrypt;var userId=$('#member_id').value;var userPw=$('#member_password').value;var saveId=$('#member_saveid').checked;if(!this.check())return;if((userId.length==0)||(userPw.length==0)){var enci='';var encp=''}else{var enci=hash.sha1(userId);var encp=hash.sha1hmac(this.publicKey,hash.md5hmac(userId,hash.md5(hash.md5(userPw))))};$('#enci').value=enci;$('#encp').value=encp;$('#saveId').checked=saveId;$('#member_id').value='';$('#member_password').value='';$('#realLoginForm').submit()}});secureLogin.set();




	