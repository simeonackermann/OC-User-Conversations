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

	public static function getConversation($offset=0, $limit=5)	
	{
		self::setUserRoomTime();
		$room = self::getRoom();

		$sql = "SELECT * FROM *PREFIX*conversations
				WHERE room = ?
				ORDER BY id DESC ";                

		$query = OCP\DB::prepare($sql, $limit, $offset);
		$conversation = $query->execute( array($room) )->fetchAll();
		return $conversation;
	}

	public static function newComment( $comment, $attachment )
	{
		self::setRoomTime();
		$room = self::getRoom();

		if ( USER_CONVERSATIONS_ATTACHMENTS &&  OCP\Share::isEnabled() && ! empty($attachment) ) {
			self::shareAttachment($attachment);
		}

		$query = OCP\DB::prepare('INSERT INTO *PREFIX*conversations (room,author,date,text,attachment) VALUES (?,?,?,?,?)');
		$query->execute( array(
							$room,
							OC_User::getUser(),
							date( 'Y-m-d H:i:s'),
							trim($comment),
							$attachment
		));		
		return true;
	}

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

	public static function getRoom()
	{
		$rooms = self::getRooms();
		return OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'activeRoom', $rooms[0]);
	}

	public static function setUserRoomTime()
	{
		OCP\Config::setUserValue(OC_User::getUser(), 'conversations', 'roomTime-' . self::getRoom(), time());
	}

	public static function setRoomTime()
	{
		OCP\Config::setAppValue('conversations', self::getRoom(), time());	
	}

	public static function updateCheck()
	{
		$result = array();
		foreach (self::getRooms() as $room) {
			$room_time = OCP\Config::getAppValue('conversations', $room, 0);
			$user_room_time = OCP\Config::getUserValue(OC_User::getUser(), 'conversations', 'roomTime-' . $room, 0);
			if ( $room_time > $user_room_time ) {
				$result[$room] = true;
			}
		}
        return $result;
	}

	public static function preparePost($post) {
		$date = self::formatDate($post['date']);
		return array(
			"id"		=> $post['id'],
			"avatar"	=> self::getUserAvatar($post['author']),
			"author"	=> OC_User::getDisplayName($post['author']),
			"date"		=> array(
				"text"	=> $date['text'],
				"val"	=> $date['val'],
			),
			"text"		=> (empty($post['text'])) ? "" : self::formatComment($post['text']),
			"attachment"=> (empty($post['attachment'])) ? "" : self::getAttachment($post['attachment']),
		);
	}

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
		//\OC_Util::setupFS($userId);
		//\OC\Files\Filesystem::initMountPoints($userId);
		$view = new \OC\Files\View('/' . $userId . '/files');
		$fileinfo = $view->getFileInfo($path);

		$download_url = OCP\Util::linkToRoute('download', array('file' => $path));		
		
		$result = array(
			"type"		=> $attachment['type'],
			"name"		=> $fileinfo['name'],
			"path"		=> $path,
			"download_url"	=> $download_url
		);
		return $result;
	}

	private static function shareAttachment($attachment) {
		$attachment = json_decode($attachment, true);
		$room = self::getRoom();

		$item_shared = self::isItemSharedWithGroup( true, 'file', $attachment['owner'], urldecode($attachment['path']) );

		/*
		$own = ( $attachment['owner'] == OC_User::getUser() ) ? true : false;
		$item_shared = self::getItemShared('file', $attachment['fileid'], $own);

		if ( ! $item_shared ) {
			$path = urldecode($attachment['path']);
			$path = substr($path, strpos($path, "/")+1 );
			$item_shared = self::getParentsShared($path);
		}
		*/
		/*
		$item_shared = false;		
		if ( $attachment['owner'] == OC_User::getUser() ) {
			//$item_sharings = OCP\Share::getItemShared('file', $attachment['fileid']);		
		} else {
			//$item_sharings = OCP\Share::getItemSharedWithBySource('file', $attachment['fileid']);
		}
		if ( is_array($item_sharings) ) {
			foreach ($item_sharings as $item_sharing) {
				if ( $item_sharing["share_type"] == OCP\Share::SHARE_TYPE_GROUP &&
					 $item_sharing["share_with"] == $room
				) {
					$item_shared = true;
				}
			}
		}
		*/
		// item not shared, share it to the current group with read permissions
		if ( ! $item_shared ) {
			\OCP\Share::shareItem('file', $attachment['fileid'], OCP\Share::SHARE_TYPE_GROUP, $room, 17);
		}
	}

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

	private static function getInheritedSharedPath($owner, $path) {
		$shared_path = substr($path, strrpos($path, "/")+1 ); // filename
		do {
			$path = substr($path, 0, strrpos($path, "/") ); // parent path
			$shared_path = substr($path, strrpos($path, "/")+1 ) . "/" . $shared_path; // shared path + parent folder
			if ( empty($path) || $path == "files" ) break;
		} while ( ! self::isItemSharedWithGroup(false, 'folder', $owner, $path) );

		return $shared_path;
	}
	/*
	private static function getItemShared($type, $fileid, $own ) {
		if ( $own ) {
			$item_sharings = OCP\Share::getItemShared($type, $fileid);
		} else {
			$item_sharings = OCP\Share::getItemSharedWithBySource($type, $fileid);
		}
		$item_shared = false;

		if ( is_array($item_sharings) && ! empty($item_sharings) ) {
			foreach ($item_sharings as $item_sharing) {
				if ( $item_sharing["share_type"] == OCP\Share::SHARE_TYPE_GROUP &&
					 $item_sharing["share_with"] == self::getRoom()
				) {
					$item_shared = true;
				}
			}
		}
		// item not shared, share it to the current group with read permissions
		if ( ! $item_shared ) {
			return false;
		} else {
			return true;
		}
	}

	private static function getParentsShared($path) { 				
		$parent_path = substr($path, 0, strrpos($path, "/") );
		if ( ! empty($parent_path) ) {
			$userId = OC_User::getUser();
			$view = new \OC\Files\View('/' . $userId . '/files');
			$parent_path_info = $view->getFileInfo($parent_path);
			if ( $parent_path_info['name'] != "Shared" ) {
				$owner = OC\Files\Filesystem::getOwner("/". $parent_path);
				$own = ( $owner == OC_User::getUser() ) ? true : false;
				$item_shared = self::getItemShared('folder', $parent_path_info['fileid'], $own);

				if ( $item_shared ) {
					return true;
				} else {
					return self::getParentsShared($parent_path);	
				}
			} 					
		}
		return false;
	}
	*/

	private static function formatComment($comment) {		
		$comment = htmlspecialchars($comment);
		$comment = nl2br($comment);		
		return $comment;
	}

	private static $avatars = array();
	private static function getUserAvatar( $user )
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
}