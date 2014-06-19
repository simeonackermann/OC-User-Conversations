OC.Conversations_App = {

	updateCheck : function() {

		$.post(OC.filePath('conversations', 'ajax', 'polling.php'), {
        }, function(jsondata) {
            if(jsondata.status == 'success' && jsondata.data.length != 0 ) {
				
				$.post(OC.filePath('conversations', 'ajax', 'getNavigationIcon.php'), { highlight: true },
		        function(jsondata) {
		            if(jsondata.status == 'success') $('#navigation li[data-id="conversations"] img').attr ("src", jsondata.icon );
		        }, 'json');
            	
            	//$('#navigation li[data-id="conversations"] a').attr ("title", 'There are new messages' );
				
				//$("html head").find("title").text("(...) Conversation - ownCloud");

            }
        }, 'json');
	}

}

$(document).ready(function(){	


	if ( $('#navigation li[data-id="conversations"] a').attr('class') != "active" ) { // TODO: certainly not the best way...!

		OC.Conversations_App.updateCheck();
		setInterval( function(){ OC.Conversations_App.updateCheck(); }, 15000);

	}

	
	
	

});