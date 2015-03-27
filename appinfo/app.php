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
/* Allow that users can delete own posts, admin can delete all */
define('USER_CONVERSATIONS_CAN_DELETE', true);

/* Allow messages to a single user */
define('UC_SINGLE_USER_MSG', true);

/* Allow private messages only to users in the same groups
	This is overwritten by the admin setting 'allow share with group members only'
	*/
define('UC_SINGLE_USER_MSG_GROUP_ONLY', false);

/* FILE ATACHMENTS 
This is a beta feature with some known bugs. It could changed in a future release without backward compatibility! */
define('USER_CONVERSATIONS_ATTACHMENTS', true);

/* end of configration ------------------------------ */


// register model-file
OC::$CLASSPATH['OC_Conversations'] = 'conversations/lib/conversations.php';

// add update script to change the app-icon even when app is not active, TODO: find app-not-active function...!
OCP\Util::addscript('conversations','updateCheck');

// register HOOK change user group
OC_HOOK::connect('OC_User', 'post_addToGroup', 'OC_Conversations', 'changeUserGroup');
OC_HOOK::connect('OC_User', 'post_removeFromGroup', 'OC_Conversations', 'changeUserGroup');

//$l=OC_L10N::get('conversations');
$l = OCP\Util::getL10N('conversations');
OCP\App::addNavigationEntry( array( 
	'id' => 'conversations_index',
	'order' => 5,
	//'href' => OCP\Util::linkTo( 'conversations', 'index.php' ),
	'href' => OCP\Util::linkToRoute('conversations_index'),
	'icon' => OCP\Util::imagePath( 'conversations', 'conversations.svg' ),
	'name' => $l->t('Conversation'),
));