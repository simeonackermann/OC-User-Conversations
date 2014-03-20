<?php if ( isset ($_['rooms']) ): ?>
	<div id="controls">			
		<form id="rooms-list">
			<label><?php p($l->t("Rooms")); ?>:</label>
			<?php foreach($_['rooms'] as $room): ?><input type="button" class="room <?php if ($room == $_['active_room']) p('active'); ?>" role="button" value="<?php p($room); ?>" /><?php endforeach; ?>
		</form>
	</div>
<?php endif; ?>

<div id="app-content">

	<form id="new-comment" data-room="<?php p($_['active_room']); ?>">
		<textarea id="new-comment-text" placeholder="<?php p($l->t("Message")); ?>..."></textarea>
		<div id="new-comment-buttons" style="display:none">
			<?php if ( USER_CONVERSATIONS_ATTACHMENTS && OCP\Share::isEnabled() ) { ?>
				<div id="new-comment-attachment" data-attachment="" style="display:none"></div>
				<a href="#" title="<?php p($l->t("Add file")); ?>" id="add-attachment"><img class="svg" alt="" src="<?php p(OC::$WEBROOT . '/core/img/places/folder.svg'); ?>" /></a>
			<?php } ?>
			<input type="submit" class="button" value="<?php p($l->t("Submit")); ?>..." disabled="disabled" />
			<br clear="both" />
		</div>
	</form>

	<div id="conversation">
		<?php
			//$userId = OC_User::getUser();
			$userId = "simi";
			\OC_Util::setupFS($userId);
			\OC\Files\Filesystem::initMountPoints($userId);
			$view = new \OC\Files\View('/' . $userId . '/files');
			$fileinfo = $view->getFileInfo('/Testshare/ordner/lilli.txt');
			var_dump($fileinfo);
			echo "<hr>";

			function isItemSharedWithGroup($recursiv, $type, $owner, $path) {	
				if ( empty($path) || $path == "files" ) 
					return false;
				\OC_Util::setupFS($owner);
				\OC\Files\Filesystem::initMountPoints($owner);
				$view = new \OC\Files\View('/' . $owner . '/files');
				$fileinfo = $view->getFileInfo( substr($path, strpos($path, "/") ) ); //get fileinfo from path (remove root folder)
				$share_type = OCP\Share::SHARE_TYPE_GROUP;
				$share_with = OC_Conversations::getRoom();

				$query = \OC_DB::prepare( 'SELECT `file_target`, `permissions`, `expiration`
											FROM `*PREFIX*share`
											WHERE `share_type` = ? AND `item_source` = ? AND `item_type` = ? AND `share_with` = ?' );

				$result = $query->execute( array($share_type, $fileinfo['fileid'], $type, $share_with) )->fetchAll();

				if ( count($result) == 0 ) { // item not shared, is parent folder shared?
					if ( $recursiv ) {
						$parent_path = substr($fileinfo['path'], 0, strrpos($fileinfo['path'], "/") );					
						return isItemSharedWithGroup(true, 'folder', $owner, $parent_path );
					} else {
						return false;
					}
				}
				return true;
			}
			function getInheritedSharedPath($owner, $path) {
				$shared_path = substr($path, strrpos($path, "/")+1 ); //
				//$path = $fileinfo['path'];				
				do {
					$path = substr($path, 0, strrpos($path, "/") ); // gte parent path
					$shared_path = substr($path, strrpos($path, "/")+1 ) . "/" . $shared_path; // shared path + parent folder
				} while ( ! isItemSharedWithGroup(false, 'folder', $owner, $path) );

				return $shared_path;				
			}
			//$item_shared = isItemSharedWithGroup(true, 'file', $userId, $fileinfo['path']);
			//var_dump($item_shared);
			//var_dump( getInheritedSharedPath($userId, $fileinfo['path']) );
			

			echo $this->inc('part.conversation');
	    ?>

	</div>

		<?php
	// Dummy navigation. Needed for endless scrolling
	if (isset($_['nextpage'])) : ?>
		<nav id="page-nav">
	  		<a href="<?php p($_['nextpage']); ?>"></a>
		</nav>
	<?php endif; ?>	

</div>

