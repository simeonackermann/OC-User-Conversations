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
OCP\JSON::callCheck();


$path = isset($_POST['path']) ? $_POST['path'] : false;

if ( $path ) {
	$room = OC_Conversations::getRoom();
	$userId = OC_User::getUser();
	\OC_Util::setupFS($userId);
	\OC\Files\Filesystem::initMountPoints($userId);
	$view = new \OC\Files\View('/' . $userId . '/files');
	$fileinfo = $view->getFileInfo($path);

	$owner = $view->getOwner($path);
		
	if ( strpos( $fileinfo['mimetype'], "image") !== false ) {
		$type = 'internal_image';
	} else {
		$type = 'internal_file';
	}

	$download_url = OCP\Util::linkToRoute('download', array('file' => $path));

	// File not found
	if ( \OC\Files\Filesystem::is_file( $path ) == false ) {
		$fileinfo['name'] = "File not found.";
		$download_url = "#";
	}

	// array for attachment template
	$tmpl_arr = array(
		"type"	=> $type,
		"mimetype"	=> $fileinfo['mimetype'],
		"path"	=> $path,
		"name"	=> $fileinfo['name'],
		"download_url" => $download_url,
	);

	// result array for new comment attachment data
	$data = array(
		"type"		=> $type,
		"fileid"	=> $fileinfo['fileid'],
		"path"		=> urlencode($fileinfo['path']),
		"owner"		=> $owner
	);

	$l=OC_L10N::get('conversations');	
	// store attachment template into variable
	$tmpl = new OCP\Template( 'conversations' , 'part.attachment' );
    $tmpl->assign( 'attachment' , $tmpl_arr );
    ob_start();
    	$tmpl->printPage();
    	echo '<p>' . ($l->t("The file will be shared with the %s group.", $room)) . '</p>';
		$html = ob_get_contents();
	ob_end_clean();

    OCP\JSON::success(array('data' => array( 'preview' => $html, 'data' => json_encode($data) )));
}