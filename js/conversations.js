OC.Conversations = {

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
            	for ( var room in jsondata.data.update) {
            		if ( room ==  $("#new-comment").attr("data-room") ) {
            			// new msgs in room
            			var last_id = $(".comment:first-child").attr("data-id");
            			OC.Conversations.LoadConversation( last_id, true );
            		} else {
            			// new msgs in other room
            			$("#rooms-list input[value=" + room + "]").addClass('new-msg');
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
	}
}

$(document).ready(function(){

	// positioning app-content if rooms-list 
	if ( $("#controls").length ) {
		// prevent window scrollbar
		$("#content").css('height', ($("#content").height()-50) + 'px');
		$("#app-content").css('top', '44px');
	}

	// active buttonbar and submit
	$("#new-comment-text").click(function(event) {
		$("#new-comment-text").css("border-width", "1px");
		$("#new-comment-buttons").show();
		$("#new-comment input[type=submit]").removeAttr( 'disabled' );		
	});
	// texteare autosize
	$("#new-comment-text").autosize();

	// select room
	$("#rooms-list .room").click(function(event) {
		var room = $(this).val();

		$("#rooms-list input").removeClass('active new-msg');
		$(this).addClass('active');

		$("#new-comment").attr("data-room", room);

		OC.Conversations.LoadConversation();		
	});	

	// submit new commnt
	$("#new-comment").submit(function(event) {
		var comment = $("#new-comment-text").val().trim();
		var attachment = $("#new-comment-attachment").attr("data-attachment");		

		if ( comment != "" || attachment != "" ) {	
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
	setInterval( function(){ OC.Conversations.polling(); }, 5000);
});