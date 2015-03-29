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

$key = isset($_POST['key']) ? $_POST['key'] : false;
$value = isset($_POST['value']) ? $_POST['value'] : "no";

if ( $key ) {

	$success = true;

	switch ( $key ) {
		case 'user_can_delete':
			setConfig( 'userCanDelete', $value );
			break;

		case 'allow_attachment':
			setConfig( 'allowAttachment', $value );
			break;

		case 'allow_single_msg':
			setConfig( 'allowPrivateMsg', $value );
			break;

		case 'group_only_private_msg':
			setConfig( 'groupOnlyPrivateMsg', $value );
			break;
		
		default:
			$success = false;
			break;
	}

	if ( $success ) {
		OCP\JSON::success(array());
	}	
}

function setConfig( $key, $value ) {	
	OCP\Config::setAppValue( 'conversations', $key, $value );
}
