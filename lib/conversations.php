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

class OC_Conversations
{

	public static function getConversation($room=false, $offset=0, $limit=5, $from_id=null, $from_date=null)
	{	
		$userId = OC_User::getUser();	
		$room = ( $room ) ? $room : self::getRoom();
		$rtype = explode(":", $room);
		// last id :http://www.php.net/manual/en/pdo.lastinsertid.php
		self::setUserRoomTime();		

		if ( $rtype[0] == "group" ) {			
			// for compatibility with UC <= 0.1.6: get room-msgs without "group:" prefix
			$and = array( "( room = ? OR room = ? )" );
			$args = array($room, $rtype[1]); // rtype[1] for compatibilty
		} else {
			// get user rooms: msgs to user X from me OR to me from user X
			$and = array( "( ( room = ? AND author = ? ) OR ( room = ? AND author = ? ) )" );
			$args = array($room, $userId, 'user:' . $userId, $rtype[1]);
		}

		// if single msgs feature enabled
		/*
		if ( USER_CONVERSATIONS_PRIVATE_MSGS ) {
			// get any room-msgs and exclusive msgs to me and from me
			$and = array( "( room = ? OR room LIKE CONCAT(?, '|".$userId."') OR ( room LIKE CONCAT(?, '|%') AND author = ? ) )" );
			array_push($args, $room, $room, $userId);
		}
		*/

		if ( $from_id ) { // used in submit new comment, to get only the new comment; TODO: could be done with with $limit
			$and[] = "id > ?";
			$args[] = $from_id;
		}

		if ( $from_date ) { // used in polling, to get only newer posts than a date
			$and[] = "date > ?";
			$args[] = $from_date;
		}

		$sql = 	"SELECT * FROM *PREFIX*conversations " .
				"WHERE " . implode(" AND ", $and) . 
				" ORDER BY id DESC";

		$query = OCP\DB::prepare($sql, $limit, $offset);
		$conversation = $query->execute( $args )->fetchAll();
		return $conversation;
	}

	public static function newComment( $comment, $attachment ) // TODO optional $room parameter
	{		
		$room = self::getRoom();
		$userId = OC_User::getUser();

		if ( USER_CONVERSATIONS_ATTACHMENTS &&  OCP\Share::isEnabled() && ! empty($attachment) ) {
			self::shareAttachment($attachment);
		}

		$query = OCP\DB::prepare('INSERT INTO *PREFIX*conversations (room,author,date,text,attachment) VALUES (?,?,?,?,?)');
		$query->execute( array(
							$room,
							$userId,
							date( 'Y-m-d H:i:s'),
							trim($comment),
							$attachment
		));	
		self::setRoomTime();	
		return true;
	}

	public static function deleteComment( $id ) {
		if ( ! USER_CONVERSATIONS_CAN_DELETE ) 
			return false;

		$query = OCP\DB::prepare('SELECT author FROM *PREFIX*conversations WHERE id = ?');
		$result = $query->execute( array($id) )->fetch();

		$uid = OC_User::getUser();
		if ( $result['author'] == $uid || OC_User::isAdminUser($uid) ) {
			$query = OCP\DB::prepare('DELETE FROM *PREFIX*conversations WHERE id = ?');
			$query->execute( array( $id ));
			return true;
		} else {
			return false;
		}
	}

	public static function getRooms() {
		$grooms = array();
		$urooms = array();
		$userId = OC_User::getUser();
		$ordering = array();
		// add groups the user contains if more than the user himself is in the room
		foreach (OC_Group::getUserGroups($userId) as $group) {
			if ( count(OC_Group::usersInGroup($group)) > 1 ) {
				$grooms["group:".$group] = array( "type" => "group", "name" => $group );
				//$ordering[] = 0 . 
				if ( UC_ROOM_ONLY_MSGS ) {
					// ##########
					// TODO: maybe private msgs only to users in same rooms
					// ##########
				}
			}
		}
		// if no group exist create default 
		if ( empty($grooms) )
			$grooms["group:default"] = array( "type" => "group", "name" => "default" );

		// add all other users
		if ( UC_ROOM_ONLY_MSGS == false ) {
			foreach (OC_User::getUsers() as $user) {
				if ( $userId != $user ) {
					$urooms["user:".$user] = array( "type" => "user", "name" => $user );
				}
			}
		}
		$rooms = $grooms + $urooms; 
		/*
			if ( $room['type'] == "group" ) {
				$rtime = $conf['rooms'][$rkey]['wtime'];
				//$lastmsg = $conf['rooms'][$rkey]['lastmsg'];
			} else {
				$u2conf = OCP\Config::getUserValue( $room['name'], 'conversations', 'conf', false );
				$u2conf = ( ! $u2conf ) ? array() : unserialize( $u2conf );
				$rtime = $u2conf['rooms']['user:'.$userId]['wtime'];
				//$lastmsg = $u2conf['rooms']['user:'.$userId]['lastmsg'];
			}
			$urtime = $uconf['rooms'][$rkey]['rtime'];
			//$ulastmsg = $uconf['rooms'][$rkey]['lastmsg'];
			if ( $rtime > $urtime ) {
				// get newer comments than last user room read time
				$new_comments = self::getConversation( $rkey, null, null, null, date( 'Y-m-d H:i:s', $urtime) );
				$result[$rkey] = array( 'newmsgs' => count($new_comments) );
			}
		*/
		return $rooms;
	}	

	// return active room from user value. If not exists return first room from rooms-list
	public static function getRoom()
	{		
		$room = OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'activeRoom', false);
		if ( $room == false ) {
			foreach (self::getRooms() as $key => $value) break; // get the first key of rooms
			$room = $key;
		}
		// TODO: return type and title array
		return $room;
	}
	/*
	public static function getRooms()
	{	
		$rooms = array();
		foreach (OC_Group::getUserGroups(OC_User::getUser()) as $group) {
			if ( count(OC_Group::usersInGroup($group)) > 1 )
				$rooms[] = $group;
		}
		if ( empty($rooms) )
			$rooms[] = "default";
		return $rooms;
	}

	// return active room from user value. If not exists return first room from rooms-list
	public static function getRoom()
	{		
		$room = OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'activeRoom', false);
		if ( $room == false ) {
			$rooms = self::getRooms();
			$room = $rooms[0];
		}
		return $room;
	}
	*/

	// write current time to user conf on reading a conversation
	public static function setUserRoomTime( $room=false ) {		
		$userId = OC_User::getUser();
		$room = ( $room ) ? $room : self::getRoom();

		$conf = OCP\Config::getUserValue( $userId, 'conversations', 'conf', false );
		$conf = ( ! $conf ) ? array() : unserialize( $conf );
		$conf['rooms'][$room]['rtime'] = time();

		OCP\Config::setUserValue( $userId, 'conversations', 'conf', serialize($conf));				
	}

	
	// write time and new message id on submit new comment 
	public static function setRoomTime( $room=false, $lastmsg=false ) {		
		$userId = OC_User::getUser();
		$room = ( $room ) ? $room : self::getRoom();
		$time = time();

		$rtype = explode(":", $room);
		$rtype = $rtype[0];		

		/*
		if ( ! $lastmsg ) {
			$query = OCP\DB::prepare("SELECT id FROM *PREFIX*conversations WHERE room = ? ORDER BY id DESC", 1, 0);
			$lastmsg = $query->execute( array($room) )->fetch();
			$lastmsg = ( !$lastmsg ) ? 0 : $lastmsg['id'];
		}		
		*/

		$conf = OCP\Config::getUserValue( $userId, 'conversations', 'conf', false );
		$conf = ( ! $conf ) ? array() : unserialize( $conf );
		//$conf['rooms'][$room]['lastmsg'] = $lastmsg;
		$conf['rooms'][$room]['wtime'] = $time;

		OCP\Config::setUserValue( $userId, 'conversations', 'conf', serialize($conf));	

		if ( $rtype == "group" ) {
			$gconf = OCP\Config::getAppValue( 'conversations', 'conf', false );
			$gconf = ( ! $gconf ) ? array() : unserialize( $gconf );
			//$gconf['rooms'][$room]['lastmsg'] = $lastmsg;
			$gconf['rooms'][$room]['wtime'] = $time;
			OCP\Config::setAppValue( 'conversations', 'conf', serialize($gconf));
		}			
	}

	// obsolet
	/*
	public static function setUserRoomTime()
	{
		//OCP\Config::setUserValue(OC_User::getUser(), 'conversations', 'roomTime-' . self::getRoom(), time());
		//self::setUserConfLastMsg();
	}
	*/

	// obsolet
	/*
	public static function setRoomTime()
	{
		//OCP\Config::setAppValue('conversations', self::getRoom(), time());	
		self::setConfLastMsg();
	}
	*/

	/*
	Test for new messages in all rooms */
	public static function updateCheck()
	{		
		$userId = OC_User::getUser();
		$result = array();

		$conf = OCP\Config::getAppValue( 'conversations', 'conf', false );
		$conf = ( ! $conf ) ? array() : unserialize( $conf );

		$uconf = OCP\Config::getUserValue( $userId, 'conversations', 'conf', false );
		$uconf = ( ! $uconf ) ? array() : unserialize( $uconf );


		foreach (self::getRooms() as $rkey => $room) {
			//$rtype = explode(":", $room);
			if ( $room['type'] == "group" ) {
				$rtime = $conf['rooms'][$rkey]['wtime'];
				//$lastmsg = $conf['rooms'][$rkey]['lastmsg'];
			} else {
				$u2conf = OCP\Config::getUserValue( $room['name'], 'conversations', 'conf', false );
				$u2conf = ( ! $u2conf ) ? array() : unserialize( $u2conf );
				$rtime = $u2conf['rooms']['user:'.$userId]['wtime'];
				//$lastmsg = $u2conf['rooms']['user:'.$userId]['lastmsg'];
			}
			$urtime = $uconf['rooms'][$rkey]['rtime'];
			//$ulastmsg = $uconf['rooms'][$rkey]['lastmsg'];
			if ( $rtime > $urtime ) {
				// get newer comments than last user room read time
				$new_comments = self::getConversation( $rkey, null, null, null, date( 'Y-m-d H:i:s', $urtime) );
				$result[$rkey] = array( 'newmsgs' => count($new_comments) );
			}
		}

		/*
		foreach (self::getRooms() as $room) {
			
			/*
			$room_time = OCP\Config::getAppValue('conversations', $room, 0);
			$user_room_time = OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'roomTime-' . $room, 0);
			if ( $room_time > $user_room_time ) {
				$result[$room] = true;
			}
		}
		*/
        return $result;
	}

	/*
	Prepare post before printing */
	public static function preparePost($post) {
		$date = self::formatDate($post['date']);
		return array(
			"id"		=> $post['id'],
			"avatar"	=> self::getUserAvatar($post['author']),
			"author"	=> self::getDisplayName($post['author'], $post['room']),
			"date"		=> array(
				"text"	=> $date['text'],
				"val"	=> $date['val'],
			),
			"text"		=> (empty($post['text'])) ? "" : self::formatComment($post['text']),
			"attachment"=> (empty($post['attachment'])) ? "" : self::getAttachment($post['attachment']),
			"room"		=> $post['room'],
		);
	}

	/* 
	format plaintext of a comment */
	private static function formatComment($comment) {		
		$comment = htmlspecialchars($comment);
		$comment = nl2br($comment);	
		$comment = preg_replace ( 
    		"/(?<!a href=\")(?<!src=\")((http|ftp)+(s)?:\/\/[^<>\s]+)/i",
    		"<a href=\"\\0\" target=\"blank\">\\0</a>",
    		$comment
		);	
		return $comment;
	}

	/*
	get attachments for printing posts */
	private static function getAttachment($attachment) {		
		$attachment = json_decode($attachment, true);		
		switch ($attachment['type']) {
			case 'internal_file':
				return self::getInternalFileAttachment($attachment);
				break;

			case 'internal_image':
				return self::getInternalFileAttachment($attachment);
				break;
			
			default:
				return array();
				break;
		}
	}	

	/*
	Get internal file attachments for printing posts */
	private static function getInternalFileAttachment($attachment) {
		$room = self::getRoom();

		$path = urldecode($attachment['path']);
		$path = substr($path, strpos($path, "/")+1 ); //remove root folder
		
		if ( $attachment['owner'] == OC_User::getUser() ) {
			// file-owner can use own path
			$path = \OC\Files\Filesystem::getPath($attachment['fileid']);
		} else {			
			$item_shared = OCP\Share::getItemSharedWithBySource('file', $attachment['fileid']);			
			if ( $item_shared != false ) { // if item is direct shared use shared-file target
				$path = "/Shared" . $item_shared['file_target'];
			} else {
				// else search shared parent folder
				$path = "/Shared/" . self::getInheritedSharedPath( $attachment['owner'], urldecode($attachment['path']) );
			}
		}
			
		$userId = OC_User::getUser();
		$view = new \OC\Files\View('/' . $userId . '/files');
		$fileinfo = $view->getFileInfo($path);

		$download_url = OCP\Util::linkToRoute('download', array('file' => $path));		

		// File not found		
		if ( \OC\Files\Filesystem::is_file( $path ) == false ) {
			$fileinfo['name'] = "File not found.";
			$download_url = "#";
		}
		
		$result = array(
			"type" => $attachment['type'],
			"mimetype"	=> $fileinfo['mimetype'],
			"name"		=> $fileinfo['name'],
			"path"		=> $path,
			"download_url"	=> $download_url
		);
		return $result;
	}

	/*
	Share item if not already done */
	private static function shareAttachment($attachment) {
		$attachment = json_decode($attachment, true);
		$room = self::getRoom();

		$item_shared = self::isItemSharedWithGroup( true, 'file', $attachment['owner'], urldecode($attachment['path']) );
		if ( ! $item_shared ) {
			\OCP\Share::shareItem('file', $attachment['fileid'], OCP\Share::SHARE_TYPE_GROUP, $room, 17);
		}
	}

	/*
	Test if item is shared with a group */
	private static function isItemSharedWithGroup($recursiv, $type, $owner, $path) {	
		if ( empty($path) || $path == "files" ) 
			return false;
		\OC_Util::setupFS($owner);
		\OC\Files\Filesystem::initMountPoints($owner);
		$view = new \OC\Files\View('/' . $owner . '/files');
		$fileinfo = $view->getFileInfo( substr($path, strpos($path, "/") ) ); //get fileinfo from path (remove root folder)
		$share_type = OCP\Share::SHARE_TYPE_GROUP;
		$share_with = self::getRoom();

		$query = \OC_DB::prepare( 'SELECT `file_target`, `permissions`, `expiration`
									FROM `*PREFIX*share`
									WHERE `share_type` = ? AND `item_source` = ? AND `item_type` = ? AND `share_with` = ?' );

		$result = $query->execute( array($share_type, $fileinfo['fileid'], $type, $share_with) )->fetchAll();

		/*
		if ( count($result) == 0 ) { // item not shared, is parent folder shared?
			$parent_path = substr($fileinfo['path'], 0, strrpos($fileinfo['path'], "/") );					
			return self::isItemSharedWithGroup( 'folder', $owner, $parent_path );
		}
		return true;
		*/
		if ( count($result) == 0 ) { // item not shared, is parent folder shared?
			if ( $recursiv ) {
				$parent_path = substr($fileinfo['path'], 0, strrpos($fileinfo['path'], "/") );					
				return self::isItemSharedWithGroup(true, 'folder', $owner, $parent_path );
			} else {
				return false;
			}
		}
		return true;
	}

	/*
	Get shared path for inherited shared item */
	private static function getInheritedSharedPath($owner, $path) {
		$shared_path = substr($path, strrpos($path, "/")+1 ); // filename
		do {
			$path = substr($path, 0, strrpos($path, "/") ); // parent path
			$shared_path = substr($path, strrpos($path, "/")+1 ) . "/" . $shared_path; // shared path + parent folder
			if ( empty($path) || $path == "files" ) break;
		} while ( ! self::isItemSharedWithGroup(false, 'folder', $owner, $path) );

		return $shared_path;
	}

	private static function getDisplayName( $author, $room ) {
		$name = OC_User::getDisplayName($author);
		// if private msgs add receiver
		if ( USER_CONVERSATIONS_PRIVATE_MSGS ) {
			$rr = explode("|", $room);
			$rrc = count($rr);
			if ( $rrc > 1 ) {
				$receiver = $rr[$rrc-1];
				$name .= " > ". $receiver;
			}			
		}
		return $name;
	}

	/*
	Get user avatar from OC */
	public static $avatars = array();
	public static function getUserAvatar( $user )
	{
		if ( ! array_key_exists($user, self::$avatars) ) {
			$avatar = New OC_Avatar($user);
			$image = $avatar->get(32);
			if ($image instanceof OC_Image) {
				$imageUrl = OC_Helper::linkToRoute ( 'core_avatar_get', array ('user' => $user, 'size' => 32) ) . '?requesttoken='. OC::$session->get('requesttoken');
				self::$avatars[$user] = $imageUrl;
			} else {
				self::$avatars[$user] = '';
			}
		}

		if ( self::$avatars[$user] != '' )
			return self::$avatars[$user];
		return '';
	}

	/*
	Get nice date from timestamp */ 
	public static function formatDate($date)
	{
		$result = array("text" => "", "val" => "");
		$date_time = strtotime($date);
		$time = time();		
		if ( $time > $date_time + (7*24*60*60) ) {
			$lang = OCP\Config::getUserValue(OC_User::getUser(), 'core', 'lang', 'en');
			if ($lang == "de") {
				$result["text"] = "%s Uhr";
				$result["val"] = date( 'd.m. - H:i', $date_time);
			} else {
				$result["text"] = "%s";
				$result["val"] = date( 'jS M h:i a', $date_time);
			}
		} elseif ( $time > $date_time + (2*24*60*60) ) {
			$days = round( ($time - $date_time) / 60 / 60 / 24 );
			$result["text"] = ( $days > 1 ) ? "%s days ago" : "%s day ago";
			$result["val"] = $days;
		} elseif ( $time > $date_time + (24*60*60) ) {
			$result["text"] = "yesterday";
		} elseif ( $time > $date_time + (60*60) ) {
			$hours = round( ($time - $date_time) / 60 / 60 );
			$result["text"] = ( $hours > 1) ? "%s hours ago" : "%s hour ago";
			$result["val"] = $hours;
		} elseif ( $time > $date_time + (60) ) {
			$minutes = round( ($time - $date_time) / 60 );
			$result["text"] = ( $minutes > 1 ) ? "%s minutes ago": "%s minute ago";
			$result["val"] = $minutes;
		} else {
			$result["text"] = "just now";
		}

		return $result;
	}

	/*
	Hook if a user is added or removed from group to change defualt room */
	public static function changeUserGroup( $args ) {
		$query = OCP\DB::prepare('DELETE FROM *PREFIX*preferences WHERE userid = ? AND appid = "conversations" AND configkey = "activeRoom"');
		$query->execute( array( $args['uid'] ));
	}
}