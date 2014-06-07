OC.Conversations_App = {

	updateCheck : function() {

		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), {
        }, function(jsondata) {
            if(jsondata.status == 'success' && jsondata.data.length != 0 ) {

            	$('#navigation li[data-id="conversations"] img').attr ("src", OC.filePath('conversations', 'img', 'conversations_red.png') );
            	//$('#navigation li[data-id="conversations"] a').attr ("title", 'There are new messages' );

            }
        }, 'json');
	}

}

$(document).ready(function(){
	
	OC.Conversations_App.updateCheck();
	setInterval( function(){ OC.Conversations_App.updateCheck(); }, 15000);

});