OC.Conversations = {

	// TODO: activeRoom : "",

	LoadConversation : function(from_id, highlight) {		
		if( typeof(from_id) === 'undefined' ) var from_id = null;
		if( typeof(highlight) === 'undefined' ) var highlight = false;
		$.post(OC.filePath('conversations', 'ajax', 'fetchConversation.php'), {
			room : $("#new-comment").attr("data-room"),
			from_id : from_id,
        }, function(jsondata) {
            if(jsondata.status == 'success') {
                if ( from_id != null ) {
                	// submit or polling request -> prepend new msgs
            		$("#conversation").prepend(jsondata.data.conversation);
            	} else {
            		// load complete room
                	$("#conversation").html(jsondata.data.conversation);
                } 
				if ( $("#new-comment-loader").length > 0 ) 
					$("#new-comment-loader").remove(); 
				if ( highlight ) 
					$(".comment:first-child").effect("bounce", {}, 400);
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
	

	polling : function() {
		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), {
        }, function(jsondata) {
            if(jsondata.status == 'success') {
            	//console.log( jsondata.data );	
            	for ( var rkey in jsondata.data) {            		
            		if ( rkey ==  $("#new-comment").attr("data-room") ) {
            			// new msgs in room
            			var last_id = $(".comment:first-child").attr("data-id");
            			OC.Conversations.LoadConversation( last_id, true );
            		} else {            			
            			// new msgs in other room
            			var newmsgs = jsondata.data[rkey].newmsgs;
            			var rkeyclass = rkey.replace(/:/g, "-");
            			//$("#rooms-list input[value=" + room + "]").addClass('new-msg');            			
            			$("li[data-room='" + rkey + "']").addClass('new-msg');
            			if ( $("#"+rkeyclass+"-new-msgs").length > 0 ) {
            				$("#"+rkeyclass+"-new-msgs").text("("+newmsgs+")");
            			} else {
            				$("li[data-room='" + rkey + "'] a").append(' <span id="'+rkeyclass+'-new-msgs" class="new-msgs-counter">('+newmsgs+')</span>');
            			}
            			
            		}
            		
            	}
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


	/*
	mesgto : [],
	
	initDropDown : function() {

		// TODO: nicht an Gruppen senden -> alle Gruppen in OC.Conversations.mesgto[OC.Share.SHARE_TYPE_GROUP]

        OC.Conversations.mesgto[OC.Share.SHARE_TYPE_USER] = [];
        OC.Conversations.mesgto[OC.Share.SHARE_TYPE_GROUP] = [];

        $('#msg-receiver').autocomplete({
            minLength : 2,
            source : function(search, response) {
                $.get(OC.filePath('core', 'ajax', 'share.php'), {
                    fetch : 'getShareWith',
                    search : search.term,
                    itemShares : [OC.Conversations.mesgto[OC.Share.SHARE_TYPE_USER], OC.Conversations.mesgto[OC.Share.SHARE_TYPE_GROUP]]
                }, function(result) {
                    if(result.status == 'success' && result.data.length > 0) {
                        response(result.data);
                    }
                });
            },
            focus : function(event, focused) {
                event.preventDefault();
            },
            select : function(event, selected) {
                var msgType = selected.item.value.shareType;
                var msgTo = selected.item.value.shareWith;
                var newitem = '<li ' + 'data-message-to="' + msgTo 
                            + '" ' + 'data-message-type="' + msgType + '">' + msgTo 
                            + ' (' + (msgType == OC.Share.SHARE_TYPE_USER ? t('core', 'user') : t('core', 'group')) + ')' 
                            +'<span class="msgactions">'+ '<img class="svg action delete" title="' + t('internal_messages', 'Quit') + '" src="' 
                            + OC.imagePath('core', 'actions/delete.svg') + '"></span></li>';
                //$('.sendto.msglist').append(newitem);
                alert(newitem);
                //$('#sharewith').val('');
                OC.Conversations.mesgto[msgType].push(msgTo);
                return false;
            },
        });
    }
    */
}

$(document).ready(function(){

	//OC.Conversations.initDropDown();

	// positioning app-content if rooms-list 
	/*
	if ( $("#controls").length ) {
		// prevent window scrollbar
		$("#content").css('height', ($("#content").height()-50) + 'px');
		$("#app-content").css('top', '44px');
	}
	*/
	if ( $("#app-navigation").length ) {
		$("#app-content").css('marginLeft', '150px');
	}

	// activate new msg buttons
	$("#new-comment-text").click(function(event) {
		$("#new-comment-text").css("border-width", "1px");
		$("#new-comment-buttons").show();
		$("#new-comment input[type=submit]").removeAttr( 'disabled' );		
	});
	// texteare autosize
	$("#new-comment-text").autosize();	

	// select room
	/*
	$("#rooms-list .room").click(function(event) {
		var room = $(this).val();

		$("#rooms-list input").removeClass('active new-msg');
		$(this).addClass('active');

		$("#new-comment").attr("data-room", room);

		OC.Conversations.LoadConversation();		
	});	
	*/
	$("#rooms li.user, #rooms li.group").click(function(event) {
		var room = $(this).attr("data-room");

		$("#rooms li").removeClass('active');
		$(this).addClass('active');

		//$("#conversation").prepend('<p><img src="'+OC.filePath('core', 'img', 'loading.gif')+'" id="new-comment-loader" /></p');

		$("#new-comment").attr("data-room", room);

		OC.Conversations.LoadConversation();		
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

	// infinite scrolling
	var $container = $('#conversation');

	$container.infinitescroll({
		navSelector  : '#page-nav',    // selector for the paged navigation
		nextSelector : '#page-nav a',  // selector for the NEXT link (to page 2)
		itemSelector : '.comment',     // selector for all items you'll retrieve
		pixelsFromNavToBottom: 150,
		extraScrollPx: 50,
		prefill: true,
		path : function(page){
			var room = $("#new-comment").attr("data-room");
			return OC.filePath('conversations', 'ajax', 'fetchConversation.php') + '?print_tmpl=true&page=' + page + '&room=' + room;
		},
		loading: {
			finishedMsg: t('conversations', 'No more comments to load'),
			msgText: t('conversations', 'Loading older comments'),
			img: OC.filePath('core', 'img', 'loading-dark.gif') 
		}
	},       
    function( nextComments ) {
		var $nextComm = $( nextComments );
		$container.append($nextComm);
	});

	// polling interval
	setInterval( function(){ OC.Conversations.polling(); }, 5000); // TODO polling-time can be increment on time
});