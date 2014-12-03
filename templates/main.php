<?php 
if ( isset ($_['rooms']) ) : 

	if ( UC_SINGLE_USER_MSG || count($_['rooms']) > 1 ) : ?>

		<div id="app-navigation">
			<ul id="rooms">
				<?php 
				$showGroups = true;
				$showUsers = true;
				$newMsgCounter = 0;
				foreach($_['rooms'] as $rid => $room) :
					$room_name = $room['name'];
					if ( $room['type'] == "group" && $showGroups ) {
						$showGroups = false; ?>
						<li class='user-label'><label><?php p($l->t("Groups")); ?></label></li>
					<?php }
					if ( $room['type'] == "user" ) {
						$room_name = OC_User::getDisplayName( $room['name'] );
						$avatar = OC_Conversations::getUserAvatar( $room['name'] );
						if ( $showUsers ) {
							$showUsers = false; ?>
							<li class='user-label'><label><?php p($l->t("User")); ?></label></li>
						<?php }
					} ?>
					<li class="<?php p($room['type']); ?> <?php if ($rid == $_['active_room']) p('active'); ?> <?php if ( isset($room['newmsgs']) ) p('new-msg'); ?>"
						data-type="<?php p($room['type']); ?>" data-room="<?php p($rid); ?>">
						<a class="" role="button">
							<?php if ( !empty($avatar) ) { ?><img src="<?php p($avatar); ?>" /><?php }
							p($room_name); ?>
							<span>
								<?php
								if ( isset($room['newmsgs']) && $rid != $_['active_room']) {
									p("(" . $room['newmsgs'] . ")"); 
									$newMsgCounter = $newMsgCounter + $room['newmsgs'];
								}
								?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" id="uc-new-msg-counter" value="<?php echo $newMsgCounter; ?>" />
		</div>

	<?php endif; ?>

	<div id="app-content">
		<form id="new-comment" data-room="<?php p($_['active_room']); ?>">
			<textarea id="new-comment-text" placeholder="<?php p($l->t("Message")); ?>..." tabindex="1"></textarea>
			<div id="new-comment-buttons" style="display:none">
				<?php if ( USER_CONVERSATIONS_ATTACHMENTS && $_['active_room'] != "group:default"  && OCP\Share::isEnabled() ) { ?>
					<div id="new-comment-attachment" data-attachment="" style="display:none"></div>
					<a href="#" title="<?php p($l->t("Add file")); ?>" id="add-attachment"><img class="svg" alt="" src="<?php p(OC::$WEBROOT . '/core/img/places/folder.svg'); ?>" /></a>
				<?php } ?>
				<input type="submit" class="button" value="<?php p($l->t("Submit")); ?>" disabled="disabled" tabindex="2" />
				<br clear="both" />
			</div>
		</form>

		<div id="conversation">
			<?php echo $this->inc('part.conversation'); ?>
		</div>

		<div id="loading_conversation" class="icon-loading"></div>
		<div id="no_more_conversation" class="hidden"><?php p($l->t("No more comments to load")); ?></div>
		<div id="no_conversation" class="hidden"><?php p($l->t("No comments to load")); ?></div>
		<audio preload src="<?php p(OC_App::getAppWebPath("conversations") . '/src/new.mp3'); ?>" id="conversations-sound-notif">
			<source src="<?php p(OC_App::getAppWebPath("conversations") . '/src/new.ogg'); ?>">
		</audio>
	</div>

	<?php 
	$ocVersion = OCP\Util::getVersion();
	if ( $ocVersion[0] >= 7 ) { ?>
		<style type="text/css">
		@media only screen and (min-width: 768px) {
			#app-content {	
				margin-left: 250px;
			}
		}
		@media only screen and (max-width: 768px) {
			#app-content {	
				padding: 37px 5px 0px 5px;
				margin-left: 0px;
			}
		}		
		</style>
	<?php } ?>

<?php else: ?>

	<div id="app-content"><div id="firstrun"><p>Sorry, coulnd't find any other user or group. Just add some in <a href="<?php echo OCP\Util::linkTo( 'index.php/settings', 'users' ); ?>">ownCloud user settings</a>.</p></div></div>

<?php endif; ?>
