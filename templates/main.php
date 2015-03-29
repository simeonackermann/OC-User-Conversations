<div id="app-navigation">
<?php if ( ! empty($_['rooms']) ) : ?>
	<?php if ( $_['allowPrivateMsg'] == "yes" || count($_['rooms']) > 1 ) : ?>
		<?php
		$showGroups = true;
		$showUsers = true;
		$newMsgCounter = 0;
		?>
		<ul id="rooms">
			<?php foreach($_['rooms'] as $rid => $room) :
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
							<?php if ( isset($room['newmsgs']) && $rid != $_['active_room']) {
								p("(" . $room['newmsgs'] . ")"); 
								$newMsgCounter = $newMsgCounter + $room['newmsgs'];
							} ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" id="uc-new-msg-counter" value="<?php echo $newMsgCounter; ?>" />
	<?php endif; ?>
<?php endif; ?>
	<?php
	if ( OC_User::isAdminUser( OC_User::getUser() ) ) { ?>
		<div id="app-settings">
			<div id="app-settings-header">
				<button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
			</div>
			<div id="app-settings-content">
				<p><strong>Admin Settings</strong></p>
				<p><input type="checkbox"<?php if ($_['userCanDelete']=="yes"): ?> checked="checked"<?php endif; ?> id="user_can_delete" />
				<label for="user_can_delete"><?php p($l->t('Users can delete their messages (admins all)'));?></label>
				</p><p>
				<input type="checkbox"<?php if ($_['allowAttachment']=="yes"): ?> checked="checked"<?php endif; ?> id="allow_attachment" />
				<label for="allow_attachment"><?php p($l->t('Enable attachments'));?></label>
				</p><p>
				<input type="checkbox"<?php if ($_['allowPrivateMsg']=="yes"): ?> checked="checked"<?php endif; ?> id="allow_single_msg" />
				<label for="allow_single_msg"><?php p($l->t('Enable private user messages'));?></label>
				</p><p>
				<input type="checkbox"<?php if ($_['groupOnlyPrivateMsg']=="yes"): ?> checked="checked"<?php endif; ?> id="group_only_private_msg" />
				<label for="group_only_private_msg"><?php p($l->t('Enable private user messages only for users in same groups'));?></label>
				</p>
			</div>
		</div>
	<?php } ?>
</div>

<div id="app-content">
<?php if ( ! empty($_['rooms']) ) : ?>
	<form id="new-comment" data-room="<?php p($_['active_room']); ?>">
		<textarea id="new-comment-text" placeholder="<?php p($l->t("Message")); ?>..." tabindex="1"></textarea>
		<div id="new-comment-buttons" style="display:none">
			<?php if ( $_['allowAttachment'] == "yes" && $_['active_room'] != "group:default"  && OCP\Share::isEnabled() ) { ?>
				<div id="new-comment-attachment" data-attachment="" style="display:none"></div>
				<a href="#" title="<?php p($l->t("Add file")); ?>" id="add-attachment"><img class="svg" alt="" src="<?php p(OC::$WEBROOT . '/core/img/places/folder.svg'); ?>" /></a>
			<?php } ?>
			<input type="submit" class="button" value="<?php p($l->t("Submit")); ?>" disabled="disabled" tabindex="2" />
			<br clear="both" />
		</div>
	</form>

	<div id="conversation"></div>

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
	<div id="no-users"><p>Please add some other users or groups in <a href="<?php echo OCP\Util::linkTo( 'index.php/settings', 'users' ); ?>">ownCloud user settings</a> to start chatting...</p></div>
<?php endif; ?>
</div>