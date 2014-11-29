OC.Conversations = {

	page: -1,
	ignoreScroll: false,

	prefill : function() {
		if ($('#app-content').scrollTop() + $('#app-content').height() > $('#conversation').height() - 100) {
			OC.Conversations.page++;

			$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
				room : $("#new-comment").attr("data-room"),
				page : OC.Conversations.page,
	        }, function(jsondata) {
	            if(jsondata.status == 'success') {
	            	if (jsondata.data.conversation.length) {
						$("#conversation").append(jsondata.data.conversation);
						
						
						OC.Conversations.prefill(); // Continue prefill
					}
					else if (OC.Conversations.page == 0) {
						// First page is empty - No activities :(
						$('#no_conversation').removeClass('hidden');
						$('#loading_conversation').addClass('hidden');
					}
					else {
						// Page is empty - No more conversation :(
						$('#no_more_conversation').removeClass('hidden');
						$('#loading_conversation').addClass('hidden');
					}
	            }
	            $("time.timeago").timeago(); // init timeago
        		$('#loading_conversation').addClass('hidden');
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
                if ( from_id != null ) {
                	// submit or polling request -> prepend new msgs
                	OC.Conversations.page = OC.Conversations.page + 0.2; // TODO this doesnt work on mor than one new msgs!
            		$("#conversation").prepend(jsondata.data.conversation);
            	} else {
            		// load complete room, MAY OBSOLTE
                	$("#conversation").html(jsondata.data.conversation);
                } 
				if ( $("#new-comment-loader").length > 0 ) 
					$("#new-comment-loader").remove(); 
				if ( highlight ) 
					$(".comment:first-child").effect("bounce", {}, 400);

				$("time.timeago").timeago(); // init timeago on change room				
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

	SetNavigationIcon : function( highlight ) {
		if( typeof(highlight) === 'undefined' ) var highlight = null;
		$.post(OC.filePath('conversations', 'ajax', 'getNavigationIcon.php'), { 'highlight': highlight },
        function(jsondata) {
            if(jsondata.status == 'success') $('#navigation li[data-id="conversations"] img').attr ("src", jsondata.icon );
        }, 'json');
	},

	polling : function() {
		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), {
        }, function(jsondata) {
            if(jsondata.status == 'success') {
            	var allNewMsgs = 0;
            	var playNotif = false;
            	var playNotifEl = $("#conversations-sound-notif").get(0);
            	for ( var rkey in jsondata.data) {
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

            			var oldNewMsg = $("li[data-room='" + rkey + "'] span").text().replace(/\(|\)/g, '');
            			oldNewMsg = parseInt(oldNewMsg);
            			if ( newmsgs > oldNewMsg ) {
            				playNotif = true;
            			}

            			$("li[data-room='" + rkey + "']").addClass('new-msg');
            			$("li[data-room='" + rkey + "'] span").text( "(" + newmsgs + ")");
            			allNewMsgs = allNewMsgs+newmsgs;
            		}
            	}
            	$("#uc-new-msg-counter").val( allNewMsgs );
            	if ( playNotif == true ) {
            		playNotifEl.play();
            	}
            	if ( allNewMsgs > 0 ) {
            		OC.Conversations.SetNavigationIcon( 'highlight' );
					//$('#navigation li[data-id="conversations"] a').attr ("title", 'There are new messages' );	
            	}
            }
        }, 'json');
	},

	onScroll: function () {
		if (!OC.Conversations.ignoreScroll && $('#app-content').scrollTop() + $('#app-content').height() > $('#conversation').height() - 100) {		 			
			OC.Conversations.ignoreScroll = true;
			OC.Conversations.page++;

			$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
				room : $("#new-comment").attr("data-room"),
				page : OC.Conversations.page,
	        }, function(jsondata) {  	
	            if(jsondata.status == 'success') {
	            	if (jsondata.data.conversation.length) {
						$("#conversation").append(jsondata.data.conversation);						
						
						OC.Conversations.ignoreScroll = false;						
					}
					else {
						// Page is empty - No more conversation :(
						$('#no_more_conversation').removeClass('hidden');
						$('#loading_conversation').addClass('hidden');
						OC.Conversations.ignoreScroll = true;
					}
	            }
	            $("time.timeago").timeago(); // init timeago
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
		$("#new-comment-text").focus(function() {
		$("#new-comment-text").css("border-width", "1px");
		$("#new-comment-buttons").show();
		$("#new-comment input[type=submit]").removeAttr( 'disabled' );		
	});
	// texteare autosize
	$("#new-comment-text").autosize();	

	// select a room
	$("#rooms li.user, #rooms li.group").click(function(event) {
		var room = $(this).attr("data-room");

		$("#rooms li").removeClass('active');
		var thisNewMsg = $(this).children().children("span").text();
		$(this).children().children("span").text("");
		$(this).removeClass('new-msg');
		$(this).addClass('active');

		
		$('#no_conversation').addClass('hidden');
		$('#no_more_conversation').addClass('hidden');
		$('#loading_conversation').removeClass('hidden');

		//OC.Util.History.pushState('room=' + room);

		//$("#conversation").prepend('<p><img src="'+OC.filePath('core', 'img', 'loading.gif')+'" id="new-comment-loader" /></p');

		$("#new-comment").attr("data-room", room);

		//OC.Conversations.LoadConversation();
		OC.Conversations.page = -1;		
		$('#conversation').animate({ scrollTop: 0 }, 'slow');
		$('#conversation').children().remove();		
		OC.Conversations.ignoreScroll = false;
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
			$("#new-comment input[type=submit]").after('<img src="'+OC.filePath('core', 'img', 'loading-small.gif')+'" id="new-comment-loader" />');

			OC.Conversations.NewComment(comment, attachment);
			
			$("#new-comment-text").val("");
			$("#new-comment-text").height("55px");
			OC.Conversations.RemoveAttachmentPreview();
			
		}
		event.preventDefault();
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
	$('#app-content').on('scroll', OC.Conversations.onScroll);

	// polling interval // TODO decrement polling-time slowly when nothing happens and on a lot of rooms
	setInterval( function(){ OC.Conversations.polling(); }, 5000);	

});

