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
	// app config var
	public static $userCanDelete;
	public static $allowAttachment;
	public static $allowPrivateMsg;
	public static $groupOnlyPrivateMsg;

	// class vars
	public static $rooms;
	public static $room;
	
							// TODO: better arguments! 
	public static function getConversation($room=false, $offset=0, $limit=5, $from_id=null, $from_date=null)
	{	
		$userId = OC_User::getUser();			
		$room = ( $room ) ? $room : self::getRoom();
		$rtype = explode(":", $room);			

		//if ( $rtype[0] == "user" && UC_SINGLE_USER_MSG == true ) {			
		if ( $rtype[0] == "user" ) {
			// get user rooms: msgs to user X from me OR to me from user X
			$and = array( "( ( room = ? AND author = ? ) OR ( room = ? AND author = ? ) )" );
			$args = array($room, $userId, 'user:' . $userId, $rtype[1]);
		} else {
			// for compatibility with UC <= 0.1.6: get room-msgs without "group:" prefix and default room in every room
			$and = array( "( room = ? OR room = ? OR room = ? )" );
			$args = array($room, $rtype[1], "default"); // rtype[1] and default for compatibilty
		} 

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

		if ( ! $offset && ! $from_date ) {
			// last id :http://www.php.net/manual/en/pdo.lastinsertid.php
			self::setUserRoomTime();
		}

		return $conversation;
	}

	public static function newComment( $comment, $attachment ) // TODO optional $room parameter
	{		
		$room = self::getRoom();
		$userId = OC_User::getUser();

		if ( OCP\Share::isEnabled() && ! empty($attachment) ) {
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
		if ( self::$userCanDelete == "no" ) 
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
		if ( self::$rooms != NULL ) {
			return self::$rooms;
		}
		// add groups the user contains if more than the user himself is in the room
		foreach (OC_Group::getUserGroups($userId) as $group) {
			if ( count(OC_Group::usersInGroup($group)) > 1 ) {
				$grooms["group:".$group] = array( "type" => "group", "name" => $group );
				$grooms["group:".$group]["lastwrite"] = self::getLastWriteTime( $grooms["group:".$group] );
			}
		}

		// add single users
		if ( self::$allowPrivateMsg == "yes" ) {
			$groupMembersOnly = false;
			if ( class_exists('OC\\Share\\Share') ) {
				$groupMembersOnly = OC\Share\Share::shareWithGroupMembersOnly();
			}

			if ( $groupMembersOnly || self::$groupOnlyPrivateMsg == "yes" ) {  // add only users in same groups
				foreach (OC_Group::getUserGroups($userId) as $group) {
					foreach (OC_Group::usersInGroup($group) as $user) {
						if ( $userId != $user ) {
							$urooms["user:".$user] = array( "type" => "user", "name" => $user );
						}
					}
				}
			} else { // add all other users
				foreach (OC_User::getUsers() as $user) {
					if ( $userId != $user ) {
						$urooms["user:".$user] = array( "type" => "user", "name" => $user );
					}
				}
			}
			// add lastwrite time and add onlinestatus as online if user is logged in and last update is not older than 31 seconds (2 polling periods)
			foreach ($urooms as $key => $value) {
				$roomName = $value["name"];

				$conf = OCP\Config::getUserValue( $roomName, 'conversations', 'conf', false );
				$conf = ( ! $conf ) ? array() : unserialize( $conf );
				if ( isset($conf["onlinestatus"]) && $conf["onlinestatus"] == "online" && ( time() - $conf["lastupdate"] ) <= 31  ) {
					$urooms["user:".$roomName]["online"] = true;
				}
				$urooms["user:".$roomName]["lastwrite"] = self::getLastWriteTime( $urooms["user:".$roomName] );
			}
		}
		$rooms = $grooms + $urooms; 
		self::$rooms = $rooms;
		return $rooms;
	}	

	// return active room from user value. If not exists return first room from rooms-list
	public static function getRoom()
	{		
		if ( self::$room != NULL ) {
			return self::$room;
		}

		$room = OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'activeRoom', false);
		if ( $room == false ) {
			foreach (self::getRooms() as $key => $value) break; // get the first key of rooms			
			$room = isset($key) ? $key : "";
		}
		self::$room = $room;
		// TODO: return type and title array $room = array( "type" => "...", "label" => ... );
		return $room;
	}

	public static function getLastWriteTime( $room ) 
	{
		$roomType = $room['type'];
		$roomName = $room['name'];
		$lastwrite = 0;
		if ( $roomType == 'group' ) {
			$lastwrite = OCP\Config::getAppValue( 'conversations', 'conf', false );
			$lastwrite = ( ! $lastwrite ) ? array() : unserialize( $lastwrite );
			if ( isset($lastwrite['rooms']['group:'.$roomName]['wtime']) ) {
				$lastwrite = $lastwrite['rooms']['group:'.$roomName]['wtime'];
			} else {
				$lastwrite = 0;
			}
		}
		if ( $roomType == 'user' ) {
			$userId = OC_User::getUser();			
			$roomKey = "user:" . $roomName;

			$conf = OCP\Config::getUserValue( $roomName, 'conversations', 'conf', false );
			$conf = ( ! $conf ) ? array() : unserialize( $conf );
			
			// add lastwr$uidite of a room (from me or the other persons config value)
			$myConf = OCP\Config::getUserValue( $userId, 'conversations', 'conf', false );
			$myConf = ( ! $conf ) ? array() : unserialize( $myConf );
			
			if ( isset($conf['rooms']['user:'.$userId]['wtime']) ) {
				$lastwrite = $conf['rooms']['user:'.$userId]['wtime'];
			}
			if ( isset($myConf['rooms'][$roomKey]['wtime']) && $myConf['rooms'][$roomKey]['wtime'] > $lastwrite ) {
				$lastwrite = $myConf['rooms'][$roomKey]['wtime'];
			}
		}
		
		return $lastwrite;
	}

	// write current time to user conf on reading a conversation or after a polling request
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
			OCP\Config::setAppValue( 'conversations', 'conf', serialize($gconf) );
		}			
	}

	/*
	Test for new messages in all rooms */
	public static function updateCheck()
	{		
		$userId = OC_User::getUser();
		$result = array();		

		$uconf = OCP\Config::getUserValue( $userId, 'conversations', 'conf', false );
		$uconf = ( ! $uconf ) ? array() : unserialize( $uconf );

		foreach (self::getRooms() as $rkey => $room) {
			//$rtype = explode(":", $room);
			$onlinestatus = false;
			if ( $room['type'] == "group" ) {
				$conf = OCP\Config::getAppValue( 'conversations', 'conf', false );
				$conf = ( ! $conf ) ? array() : unserialize( $conf );
				$wtime = isset($conf['rooms'][$rkey]['wtime']) ? $conf['rooms'][$rkey]['wtime'] : 0;
				//$lastmsg = $conf['rooms'][$rkey]['lastmsg'];
			} else {
				$u2conf = OCP\Config::getUserValue( $room['name'], 'conversations', 'conf', false );
				$u2conf = ( ! $u2conf ) ? array() : unserialize( $u2conf );
				$wtime = isset($u2conf['rooms']['user:'.$userId]['wtime']) ? $u2conf['rooms']['user:'.$userId]['wtime'] : 0;
			}
			$urtime = isset($uconf['rooms'][$rkey]['rtime']) ? $uconf['rooms'][$rkey]['rtime'] : 0;

			if ( $wtime > $urtime ) {
				// get newer comments than last user room read time
				$new_comments = self::getConversation( $rkey, null, null, null, date( 'Y-m-d H:i:s', $urtime) );
				$result[$rkey] = array( 'newmsgs' => count($new_comments), 'lastwrite' => self::getLastWriteTime($room) );
			}
			// write onlinestatus
			if ( isset($room['online']) ) {
					$result[$rkey]["online"] = true;
			}
		}
        return $result;
	}

	/*
	Prepare post before display */
	public static function preparePost($post) {
		$dateTimeObj = new DateTime($post['date']);
		return array(
			"id"		=> $post['id'],
			"author"	=> $post['author'],
			"date"		=> array(
								'ISO8601' => $dateTimeObj->format(DateTime::ISO8601),
								'datetime'=>  date( 'Y-m-d H:i\h', strtotime($post['date']) )
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
		$result = array();

		$userId = OC_User::getUser();
		$view = new \OC\Files\View('/' . $userId . '/files');

		$path = $view->getPath( $attachment['fileid'] );
		$mimetype = $view->getMimeType( $path );
		$download_url = OCP\Util::linkToRoute('download', array('file' => $path));
		$name = basename($path);
		$type = $attachment['type'];
		if ( $type == 'internal_image' ) {
			$icon_url = OC::$WEBROOT . "/index.php/core/preview.png?x=200&y=200&file=" . urlencode($path);
		} else {
			$icon_url = OC_Helper::mimetypeIcon( $mimetype );
		}

		// File not found		
		if ( $view->is_file( $path ) == false ) {
			$name = "File not found.";
			$download_url = "#";
		}
		
		$result = array(
			"type" => $type,
			"mimetype"	=> $mimetype,
			"name"		=> $name,
			"path"		=> $path,
			"download_url"	=> $download_url,
			"icon_url" => $icon_url			
		);

		return $result;
	}		

	/*
	Share item if not already done */
	private static function shareAttachment($attachment) {
		$attachment = json_decode($attachment, true);
		$room = self::getRoom();
		$room = explode(":", $room);

		$isShared = self::isItemShared($attachment['fileid']);
		if ( ! $isShared ) {
			$share_type = ( $room[0] == "group" ) ? OCP\Share::SHARE_TYPE_GROUP : OCP\Share::SHARE_TYPE_USER;
			\OCP\Share::shareItem('file', $attachment['fileid'], $share_type, $room[1], 17);
		}
	}

	/*
	test if item is shared with current user(s) in room by its fileid */
	public static function isItemShared($fileid) {	
		$room = self::getRoom();
		$room = explode(":", $room);
		$userId = OC_User::getUser();
		$isShared = true;
		$view = new \OC\Files\View('/' . $userId . '/files');

		$path = $view->getPath( $fileid );
		$owner = $view->getOwner($path);

		$userSharingFile = OCP\Share::getUsersSharingFile( $path, $owner );
		if ( $room[0] == "group" ) {
			foreach (OC_Group::usersInGroup($room[1]) as $user) {
				if ( $owner != $room && ! in_array($user, $userSharingFile["users"]) ) {
					$isShared = false;
				}
			}	
		} else {
			if ( $owner != $room[1] && !in_array($room[1],  $userSharingFile["users"] ) ) {
				$isShared = false;
			}
		}

		return $isShared;
	}

	/*
	Hook if a user is added or removed from group to change defualt room */
	public static function changeUserGroup( $args ) {
		$query = OCP\DB::prepare('DELETE FROM *PREFIX*preferences WHERE userid = ? AND appid = "conversations" AND configkey = "activeRoom"');
		$query->execute( array( $args['uid'] ));
	}

	public static function hook_login($uid) {
		self::updateUserOnlineStatus( $uid['uid'] );
	}

	public static function hook_logout() {
		$uid = OC_User::getUser();
		self::updateUserOnlineStatus( $uid, "offline" );
	}

	public static function updateUserOnlineStatus($uid, $value="online") {
		$conf = OCP\Config::getUserValue( $uid, 'conversations', 'conf', false );
		$conf = ( ! $conf ) ? array() : unserialize( $conf );
		$conf["onlinestatus"] = $value;
		$conf["lastupdate"] = time();

		OCP\Config::setUserValue( $uid, 'conversations', 'conf', serialize($conf));
	}
}