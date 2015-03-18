<?php

/**
* ownCloud - User Conversations
*
* @author Simeon Ackermann
* @copyright 2014 Simeon Ackermann amseon@web.de
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Affero General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OCP\JSON::checkAppEnabled('conversations');
OCP\JSON::checkLoggedIn();
//OCP\JSON::callCheck();

$room = isset( $_REQUEST['room'] ) ? $_REQUEST['room'] : false;

// TODO: remove room argument!

if ( $room ) {

	// store room as user default
	OCP\Config::setUserValue(OC_User::getUser(), 'conversations', 'activeRoom', $room);

	$count = 5;
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 0;

	$from_id = isset($_REQUEST['from_id']) ? intval($_REQUEST['from_id']) : null;

	// load room
    $tmpl = new OCP\Template( 'conversations' , 'part.conversation' );
    $tmpl->assign( 'conversation' , OC_Conversations::getConversation(false, $page * $count, $count, $from_id) );

    $conversation = $tmpl->fetchPage();
	OCP\JSON::success(array('data' => array( 'conversation' => $conversation  )));
}