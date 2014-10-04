var IRC5 = (function(window, document, $) {

	var socket,
	connected=false,
	ircConnected=false,
	ircHost = '',
	firstMsg = true;
	
	function sortNicks() {
		var mylist = $('.items');
		var listitems = mylist.children('li').get();
		listitems.sort(function(a, b) {
			var compA = $(a).find('span').text().toLowerCase();
			var compB = $(b).find('span').text().toLowerCase();
			return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		});
		$.each(listitems, function(idx, itm) { mylist.append(itm); });
	}
	
	function init(){
		var host = "ws://crushhour.net:12345/IRC5/tServer.php";
		try{
			socket = new WebSocket(host);
			log('WebSocket - status '+socket.readyState);
			socket.onopen    = function(msg){ 
				log("Welcome - status "+this.readyState); 
				connected=true;
				send('###IRC');
				
			};
			socket.onmessage = function(msg){ 
				var	tmp=msg.data.split(" "),
					msgStr = msg.data,
					nameArray,
					initMSG,
					naList='',
					nameL;
				if (firstMsg) {
					if (msgStr.substr(1,6) == "###YUP" ) {
						initMSG = "USER websocket IRC5 _druu whatever\r\n";
						if($('#ircNICK').val()) {initMSG += "NICK "+ $('#ircNICK').val() + "\r\n";}
						if($('#ircCHAN').val()) {initMSG += "JOIN "+ $('#ircCHAN').val() + "\r\n";}
						send (initMSG);
						return;
					}
					else {
						ircHost = tmp[0].substr(1);
						firstMsg = false;
					}
				}
				
				
				console.log(tmp);
				
				if (tmp[0] === "PING") {
				send('PONG :'+ircHost);
				return;
				}
				
				switch (tmp[1]) {
					
					case "353":
						nameArray = msg.data.substr(msg.data.lastIndexOf(':')+1).split(" ");
						naList = '<li class="'+(nameArray[0]==$('#ircNICK').val()?"current":'')+'"><span class="name">'+nameArray.join('</span></li><li><span class="name"">')+'</span></li>';
						$('.items').append(naList);
						sortNicks();
						break;
					case "PRIVMSG":
						log(
							'<span class="nick">' +
							tmp[0].substr(1,tmp[0].indexOf('!')-1) +
							'</span>: <span class="msg">' +
							msg.data.substr(msg.data.indexOf(':',5)+1)+
							'</span>'
						);
						break;
					
					case "JOIN":
						if (tmp[0].substr(1,tmp[0].indexOf('!')-1) == $('#ircNICK').val()){break;}
						log(
							"***"+
							tmp[0].substr(1,tmp[0].indexOf('!')-1) +
							' joined the room.'
						);
						$('.items').empty();
						send('NAMES :'+$('#ircCHAN').val())
						break;
						
					case "PART":
					case "QUIT":
						log(
							"***"+
							tmp[0].substr(1,tmp[0].indexOf('!')-1) +
							' has left the room. ('+
							msg.data.substr(msg.data.indexOf(':',5)+1) +
							')</span>'
						);
						$('.items').empty();
						send('NAMES :'+$('#ircCHAN').val())
						break;
						
					default:
						log("> "+msg.data); 
				}
				
			};
			socket.onclose   = function(msg){ log("Disconnected - status "+this.readyState); };
		}
		catch(ex){ log(ex); }
	}

	function send( dmsg ){
		if (dmsg) {
			try{ socket.send(dmsg); log('< '+dmsg); } catch(ex){ log(ex); }
			return;
		}
		var txt,msg;
		txt = $("#ircMSG");
		msg = txt.val();
		if(!msg){ alert("Message can not be empty"); return; }
		txt.value="";
		txt.focus();
		if (connected && msg.substr(0,1) != "/") {
			log('<span class="self">'+$('#ircNICK').val()+'<span>: <span class="msg">'+msg+'</span>');
			msg = "PRIVMSG "+$('#ircCHAN').val()+" :"+msg;
			try{ socket.send(msg);  } catch(ex){ log(ex); }
		}
		if(msg =="/quit") {
			try{ socket.send('QUIT :leaving on a websocket'); log('< '+msg); } catch(ex){ log(ex); }
			quit();
		}
		$("#ircMSG").val('');
	}
	function quit(){
		log("Goodbye!");
		socket.close();
		socket=null;
	}

	// Utilities
	function log(msg){ 
		$(".content").append('<div>'+msg+'</div>'); $(".content").attr({ scrollTop: $(".content").attr("scrollHeight") });
	}

	return {
		connect: init,
		send: send,
		quit: quit,
		host: ircHost,
		sort: sortNicks
	};

	})(this, this.document, jQuery);


	jQuery(document).ready(function(){

		jQuery('#ircMSG').live('keyup',function(event){ if(event.keyCode==13){ IRC5.send(); return;} });
		jQuery('.ircConnect').live('click', IRC5.connect);
		
	});