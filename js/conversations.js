OC.Conversations = {

	page: -1,
	ignoreScroll: false,
	windowFocus: true,
	avatars: {},

	prefill : function() {
		if ($('#app-content').scrollTop() + $('#app-content').height() > $('#conversation').height() - 100) {
			OC.Conversations.page++;			

			$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
				room : $("#new-comment").attr("data-room"),
				page : OC.Conversations.page,
	        }, function(jsondata) {
	            if(jsondata.status == 'success') {
	            	if (jsondata.data.conversation.length > 0) {
						$("#conversation").append(jsondata.data.conversation);
						
						OC.Conversations.prefill(); // Continue prefill
						$.timeago.settings.localeTitle = true;
	            		$("time.timeago").timeago(); // init timeago
	            		OC.Conversations.ignoreScroll = false;
	            		$('#app-content').scroll(OC.Conversations.onScroll);
					}
					else if (OC.Conversations.page == 0) {
						// First page is empty - No activities :(
						$('#no_conversation').removeClass('hidden');
					}
					else {
						// Page is empty - No more conversation :(
						$('#no_more_conversation').removeClass('hidden');
					}
	            }	            
        		$('#loading_conversation').addClass('hidden');
        		OC.Conversations.ShowAvatar( $(".app-conversations") );
        		
	        }, 'json');
		}		
	},

	LoadConversation : function(from_id, highlight) {	// TODO abspecken wegen prefill
		if( typeof(from_id) === 'undefined' ) var from_id = null;
		if( typeof(highlight) === 'undefined' ) var highlight = false;
		$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
			room : $("#new-comment").attr("data-room"), // TODO: remove room argument!
			from_id : from_id,
        }, function(jsondata) {
            if(jsondata.status == 'success') {
            	var newMsgs = $.parseHTML( jsondata.data.conversation );
            	if ( from_id != null ) {
                	// submit or polling request -> prepend new msgs
                	OC.Conversations.page = OC.Conversations.page + 0.2; // TODO this doesnt work on mor than one new msgs!
            		$("#conversation").prepend(newMsgs);
            	} else {
            		// load complete room, MAY OBSOLTE
                	$("#conversation").html(newMsgs);
                } 
				$("#new-comment-loader").hide();
				if ( highlight ) 
					$(".comment:first-child").effect("highlight", {}, 500);

				$("time.timeago").timeago(); // init timeago on change room
				OC.Conversations.ShowAvatar( newMsgs );				
            }
        }, 'json');
	},

	NewComment : function(comment, attachment) {
		$.post(OC.filePath('conversations', 'ajax', 'newComment.php'), {
            comment : comment ,
            attachment : attachment
        }, function(jsondata) {
            if(jsondata.status == 'success') {
                var last_id = $(".comment:first-child").attr("data-id");
                OC.Conversations.LoadConversation( last_id );
                $("li[data-room='" + $("#new-comment").attr("data-room") + "'] .navtimeago").timeago( 'update', new Date() );
            }
        }, 'json');
	},

	DeleteComment : function(id) {
		$.post(OC.filePath('conversations', 'ajax', 'deleteComment.php'), {
            id : id 
        }, function(jsondata) {
            if(jsondata.status == 'success') {
            	$(".comment[data-id='"+id+"']").slideUp();
            } 
        }, 'json');
	},	

	AddAttachment : function(path) {
		$("#new-comment-attachment").show();
		$("#add-attachment").hide();
		$.post(OC.filePath('conversations', 'ajax', 'attachmentPreview.php'), {
			path : path
		}, function(jsondata) {
			if (jsondata.status == 'success') {
				$("#new-comment-attachment").attr('data-attachment', jsondata.data.data);
				$("#new-comment-attachment").html('<a class="oc-dialog-close svg">&nbsp;&nbsp;</a>' + jsondata.data.preview);
				$("#new-comment-attachment .oc-dialog-close").click(function() { OC.Conversations.RemoveAttachmentPreview(); }); 	
			}
		}, 'json');
	},

	RemoveAttachmentPreview : function() {		
		$("#new-comment-attachment").hide();
		$("#new-comment-attachment").html("");
		$("#new-comment-attachment").attr("data-attachment", "");
		$("#add-attachment").show();
	},

	ShowAvatar : function( env ) {
		var self = this;
		$.each( $('.avatar', env ), function( i, div ) {
			var user = $(div).attr("data-user");
			$(div).hide();
			if ( self.avatars.hasOwnProperty(user) ) {	
				$(div).replaceWith( '<div class="avatar" data-user="'+user+'">'+self.avatars[user]+'</div>' );
			} else {
				self.avatars[user] = '';
				$(div).avatar( user, 32, undefined, true, function( a ) {
					if ( $(div).html() != "" ) {
						self.avatars[user] = $(div).html();
						self.ShowAvatar( env );
					}
				});
			}
		});
	},

	SetNavigationIcon : function( highlight ) {		
		var iconSrc = OC.appswebroots.conversations + "/img/";
		if ( typeof(highlight) === 'undefined' ) {
			iconSrc += "conversations.svg";
			document.title =  t('conversations', 'Conversation') + " - ownCloud";
		} else {
			iconSrc += "conversations_red.svg";
			document.title =  t('conversations', 'New comments') + " | " + t('conversations', 'Conversation') + " - " + OC.theme.title;
		}
		$('#navigation li[data-id="conversations_index"] img').attr ("src", iconSrc );		
	},

	polling : function() {
		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), {
        }, function(jsondata) {
        	if(jsondata.status == 'success') {
            	var hasNewMsgs = false;
            	var allNewMsgs = 0;
            	var playNotif = false;
            	var playNotifEl = $("#conversations-sound-notif").get(0);
            	for ( var rkey in jsondata.data) {
            		// test if room has a new msg
            		if ( jsondata.data[rkey].hasOwnProperty("newmsgs") ) {
            			hasNewMsgs = true;
            			if ( rkey ==  $("#new-comment").attr("data-room") ) {
	            			// TODO BUG: dont poll if user submitted a new post until its completed
	            			// new msgs in current room
	            			var last_id = $(".comment:first-child").attr("data-id");
	            			playNotif = true;
	            			OC.Conversations.LoadConversation( last_id, true );
	            		} else {
	            			// new msgs in other room
	            			var newmsgs = jsondata.data[rkey].newmsgs;
	            			var rkeyclass = rkey.replace(/:/g, "-");

	            			var oldNewMsg = $("li[data-room='" + rkey + "'] span.new-msg-counter-room").text().replace(/\(|\)/g, '');
	            			oldNewMsg = parseInt(oldNewMsg);
	            			if ( newmsgs > oldNewMsg ) {
	            				playNotif = true;
	            			}
	            			var lastwrite = new Date( parseInt(jsondata.data[rkey].lastwrite) * 1000 );

	            			$("li[data-room='" + rkey + "']").addClass('new-msg');
	            			$("li[data-room='" + rkey + "'] span.new-msg-counter-room").text( "(" + newmsgs + ")");	            			
							$("li[data-room='" + rkey + "'] .navtimeago").timeago( 'update', lastwrite );

	            			allNewMsgs = allNewMsgs+newmsgs;
	            		}
            		}   
            		// may add online status
            		if ( jsondata.data[rkey].hasOwnProperty("online") ) {
            			$("li[data-room='" + rkey + "'] img.online").css("display","inline-block");
            		} else {
            			$("li[data-room='" + rkey + "'] img.online").css("display","none");
            		}
            	}            	
            	$("#uc-new-msg-counter").val( allNewMsgs );
            	if ( playNotif == true ) {
            		playNotifEl.play();
            	}
            	// set document title if window doesnt has focus or new msg in other rooms
            	if ( hasNewMsgs ) {
            		if ( ! OC.Conversations.windowFocus ) {
	            		OC.Conversations.SetNavigationIcon( 'highlight' );
	            	}
	            	else if ( OC.Conversations.windowFocus && allNewMsgs > 0 ) {
	            		OC.Conversations.SetNavigationIcon( 'highlight' );
	            	}
            	} else {
            		OC.Conversations.SetNavigationIcon();
            	}
            	if ( jsondata.data.length == 0 ) {
            		$("li[data-room] img.online").css("display","none");
            	}
            }
        }, 'json');
	},

	onScroll: function () {
		if (!OC.Conversations.ignoreScroll && $('#app-content').scrollTop() + $('#app-content').height() > $('#conversation').height() - 100) {		 			
			OC.Conversations.ignoreScroll = true;
			OC.Conversations.page++;
			$('#loading_conversation').removeClass('hidden');

			$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
				room : $("#new-comment").attr("data-room"),
				page : OC.Conversations.page,
	        }, function(jsondata) {  	
	            if(jsondata.status == 'success') {	            	
	            	if (jsondata.data.conversation.length > 0) {
	            		var newMsgs = $.parseHTML(jsondata.data.conversation);
						$("#conversation").append(newMsgs);
						
						OC.Conversations.ignoreScroll = false;
						$("time.timeago").timeago(); // init timeago
						OC.Conversations.ShowAvatar(newMsgs);
					}
					else {
						// Page is empty - No more conversation :(
						$('#no_more_conversation').removeClass('hidden');
						OC.Conversations.ignoreScroll = true;
					}					
	            }	            
        		$('#loading_conversation').addClass('hidden');
	        }, 'json');
		}
	},
}

$(document).ready(function(){

	// set default app icon on entering app when no new msgs
	if ( $("#uc-new-msg-counter").val() == 0 ) {
		OC.Conversations.SetNavigationIcon();
	}

	// activate new msg buttons	
	$("#new-comment-text").on( 'click keyup', function() { 
		$("#new-comment-text").css("border-width", "1px");
		$("#new-comment-buttons").fadeIn();
		$("#new-comment input[type=submit]").removeAttr( 'disabled' );		
		$("#new-comment-text").off('click keyup');
	});
	// texteare autosize
	$("#new-comment-text").autosize();	

	// select a room
	$("#rooms li.user, #rooms li.group").click(function(event) {
		var room = $(this).attr("data-room");

		$("#rooms li").removeClass('active');
		var thisNewMsg = $(this).children().children("span.new-msg-counter-room").text();
		$(this).children().children("span.new-msg-counter-room").text("");
		$(this).removeClass('new-msg');
		$(this).addClass('active');
		
		$('#no_conversation').addClass('hidden');
		$('#no_more_conversation').addClass('hidden');
		$('#loading_conversation').removeClass('hidden');

		//OC.Util.History.pushState('room=' + room);
		$("#new-comment").attr("data-room", room);
		$("#new-comment-text").focus();

		//OC.Conversations.LoadConversation();
		OC.Conversations.page = -1;		
		$('#conversation').animate({ scrollTop: 0 }, 'fast');
		$('#conversation').children().remove();		
		OC.Conversations.ignoreScroll = true;
		OC.Conversations.prefill();

		// set default app icon when all room-messages where read
		if ( thisNewMsg != "" ) {
			thisNewMsg = parseInt( thisNewMsg.substring( 1, thisNewMsg.length-1 ) );
			var allNewMsgs = parseInt( $("#uc-new-msg-counter").val() );
			$("#uc-new-msg-counter").val( allNewMsgs-thisNewMsg );
			if ( $("#uc-new-msg-counter").val() == "0" ) {
				OC.Conversations.SetNavigationIcon();
			}
		}
	});

	// submit new commnt
	$("#new-comment").submit(function(event) {
		var comment = $("#new-comment-text").val().trim();
		var attachment = $("#new-comment-attachment").attr("data-attachment");		

		if ( comment != "" || ( attachment != undefined && attachment != "" ) ) {
			$("#new-comment-loader").show();
			OC.Conversations.NewComment(comment, attachment);
			
			$("#new-comment-text").val("");
			$("#new-comment-text").height("55px");
			OC.Conversations.RemoveAttachmentPreview();
		}
		event.preventDefault();
	});

	// submit commit with ctrl+enter
	$(document).bind('keypress', function(event) {
		if( event.which === 13 && event.ctrlKey ) {
			$("#new-comment").submit();
		}
	});

	// add attachment
	$('#add-attachment').click(function(){
		OC.dialogs.filepicker(t('conversations', 'Select file'),OC.Conversations.AddAttachment, false, [], true);
	});

	//delete message 
	$(document).on('click',".comment-header a.action.delete",function() {
		var id = $(this).parent().parent().parent(".comment").attr("data-id");
		OC.Conversations.DeleteComment(id);
		return false;
	});

	// first fill app-content
	OC.Conversations.prefill();
	//$('#app-content').on('scroll', OC.Conversations.onScroll);

	// set title for rooms
	function setRoomTitle( el ) {
		groupUsers = "";
		var groupUsersEl = $(el).parent().find(".group-room-users");
		if ( groupUsersEl.length > 0 ) {
			groupUsers = t('conversations', 'User') + ": " + groupUsersEl.text() + "\n";
		}
		if ( $(el).attr("datetime").substring(0, 10) == "1970-01-01" ) {
			$(el).parent().attr( "title", groupUsers + t('conversations', 'No comments') );
		} else {
			$(el).parent().attr( "title", groupUsers + t('conversations', 'Last comment') + ": " + $(el).text() );	
		}
	}

	// set timego in rooms of last comment
	$("time.navtimeago").bind('timeagoupdate', function() {
		setRoomTitle( $(this) );
	})
	$("time.navtimeago").timeago();
	$("time.navtimeago").each(function() { setRoomTitle( $(this) ); });	

	// polling interval // TODO decrement polling-time slowly when nothing happens and on a lot of rooms
	setInterval( function(){ OC.Conversations.polling(); }, 5000);	

	// get if window has focus (need it for document title)
	$(window).focus(function() {
	    OC.Conversations.windowFocus = true;
	}).blur(function() {
	    OC.Conversations.windowFocus = false;
	});

	// app settings
	$('#app-settings-content input').change(function () {
		$.post(OC.filePath('conversations', 'ajax', 'settings.php'), {
            key : $(this).attr("id"),
            value : this.checked ? "yes" : "no"
        }, function(jsondata) {
            if(jsondata.status == 'success') {
            	window.location.reload();
            } 
        }, 'json');
	});

});

