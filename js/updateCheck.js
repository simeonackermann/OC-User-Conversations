OC.Conversations_App = {

	updateCheck : function() {

		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), function(jsondata) {
            if(jsondata.status == 'success' && jsondata.data.length != 0 ) {
            	
            	var iconSrc = OC.appswebroots.conversations + "/img/conversations_red.png";
            	$('#navigation li[data-id="conversations_index"] img').attr ("src", iconSrc );
            	
            	//$('#navigation li[data-id="conversations"] a').attr ("title", 'There are new messages' );

            }
        }, 'json');
	}

}

$(document).ready(function(){

	if ( $('#navigation li[data-id="conversations_index"] a').attr('class') != "active" ) { // TODO: certainly not the best way...!

		OC.Conversations_App.updateCheck();
		setInterval( function(){ OC.Conversations_App.updateCheck(); }, 15000);

	}

});