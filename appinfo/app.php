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

/*
### CONFIG ###
----------------------------------------------------- */
/* User can delete own posts, admin can delete all */
define('USER_CONVERSATIONS_CAN_DELETE', true);


define('UC_ROOM_ONLY_MSGS', false);


/* FILE ATACHMENTS 
This is an alpha feature with some known bugs (see todo.txt). It could changed in a future release without backward compatibility! */
define('USER_CONVERSATIONS_ATTACHMENTS', true);

/* end of configration ------------------------------ */


// register model-file
OC::$CLASSPATH['OC_Conversations'] = 'conversations/lib/conversations.php';

// register change user group
OC_HOOK::connect('OC_User', 'post_addToGroup', 'OC_Conversations', 'changeUserGroup');
OC_HOOK::connect('OC_User', 'post_removeFromGroup', 'OC_Conversations', 'changeUserGroup');

$icon = 'conversations.png';
$updates = OC_Conversations::updateCheck();
if ( ! empty ( $updates ) ) {
	$icon = 'conversations_red.png';
}

$l=OC_L10N::get('conversations');
OCP\App::addNavigationEntry( array( 
	'id' => 'conversations',
	'order' => 5,
	'href' => OCP\Util::linkTo( 'conversations', 'index.php' ),
	'icon' => OCP\Util::imagePath( 'conversations', $icon ),
	'name' => $l->t('Conversation'),
));
