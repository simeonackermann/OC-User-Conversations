OC.Conversations_App = {

	updateCheck : function() {
		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), function(jsondata) {
			if(jsondata.status == 'success' && jsondata.data.length != 0 ) {
				var hasNewMsgs = false;
				for ( var rkey in jsondata.data) {
            		if ( jsondata.data[rkey].hasOwnProperty("newmsgs") ) {
            			hasNewMsgs = true;
            			break;
            		}
            	}
            	if ( hasNewMsgs ) {
            		// update conversations app-menu icon
					var iconSrc = OC.appswebroots.conversations + "/img/conversations_red.svg";
					$('#navigation li[data-id="conversations_index"] img').attr ("src", iconSrc );

					// add notif-icon in header bar for OC > 8
					if ( parseInt(OC.config.version) >= 8 && $(".conversations-notification").length < 1 ) {
						var conversationsUrl = OC.generateUrl('apps/conversations/');
						$("#header .menutoggle").css("left", "105px");
						$("#header .menutoggle").after('<div class="menutoggle conversations-notification" style="position:absolute;left:70px;padding-top:24px">' + 
							'<a href="'+conversationsUrl+'" title="'+t('conversations', 'Conversation')+": "+t('conversations', 'New comments') +'" class="header-appname">' +
								'<img src="'+iconSrc+'" style="width:80%" />'+
							'</a></div>');
					}
            	}				
			}
		}, 'json');
	}

}

$(document).ready(function(){

	// do updateCheck if app is not active
	if ( $('#navigation li[data-id="conversations_index"] a').attr('class') != "active" ) { // TODO: certainly not the best way...!
		OC.Conversations_App.updateCheck();
		setInterval( function(){ OC.Conversations_App.updateCheck(); }, 15000);
	}	

});