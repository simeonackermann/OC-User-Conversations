<?php 
/*
if ( isset ($_['rooms']) ): ?>
	<div id="controls">			
		<form id="rooms-list">
			<label><?php p($l->t("Rooms")); ?>:</label>
			<?php foreach($_['rooms'] as $room): ?><input type="button" class="room <?php if ($room == $_['active_room']) p('active'); ?>" role="button" value="<?php p($room); ?>" /><?php endforeach; ?>
		</form>
	</div>
<?php endif; 
*/ ?>

<?php 
if ( isset ($_['rooms']) ): ?>
	<div id="app-navigation">
		<ul id="rooms">		
			<li><label><?php p($l->t("Rooms")); ?>:</label></li>
			<?php
			$pul=true;
			foreach($_['rooms'] as $rid => $room):
				if ( $room['type'] == "user" && $pul ) {
					$pul=false;
					?><li class='user-label'><label><?php p($l->t("User")); ?>:</label></li><?php
				}
				?>
				<li class="<?php p($room['type']); ?> <?php if ($rid == $_['active_room']) p('active'); ?>" 
					data-type="<?php p($room['type']); ?>" data-room="<?php p($rid); ?>">
					<a class="" role="button">
					<?php
					if ( $room['type'] == "user" ) {
						$avatar = OC_Conversations::getUserAvatar( $room['name'] );
						if ( !empty($avatar) ) { ?>
							<img src="<?php p($avatar); ?>" />
						<?php } else {
							//echo "<img src='".OCP\Util::imagePath( 'conversations', 'person.png' )."' />";
						}
					}
					?>
					<?php p($room['name']); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<div id="app-content">

	<?php 		
	//$update = OC_Conversations::updateCheck();
	//var_dump($update);
	//var_dump( OC_Conversations::getRooms() );
	?>

	<form id="new-comment" data-room="<?php p($_['active_room']); ?>">
		<textarea id="new-comment-text" placeholder="<?php p($l->t("Message")); ?>..."></textarea>
		<div id="new-comment-buttons" style="display:none">
			<?php if ( USER_CONVERSATIONS_ATTACHMENTS && OCP\Share::isEnabled() ) { ?>
				<div id="new-comment-attachment" data-attachment="" style="display:none"></div>
				<a href="#" title="<?php p($l->t("Add file")); ?>" id="add-attachment"><img class="svg" alt="" src="<?php p(OC::$WEBROOT . '/core/img/places/folder.svg'); ?>" /></a>
			<?php } 
			/*
			if ( USER_CONVERSATIONS_PRIVATE_MSGS ) { ?>
				<label for="msg-receiver"><img class="svg" alt="" src="<?php p(OC::$WEBROOT . '/core/img/actions/user.svg'); ?>" /></label> <input id="msg-receiver" type="text" name="" placeholder="<?php echo $l -> t("Message to ...");?>" class="ui-autocomplete-input" autocomplete="off" />
			<?php }
			*/ ?>
			<input type="submit" class="button" value="<?php p($l->t("Submit")); ?>..." disabled="disabled" />
			<br clear="both" />
		</div>
	</form>

	<div id="conversation">
		<?php echo $this->inc('part.conversation'); ?>
	</div>

	<?php
	// Dummy navigation. Needed for endless scrolling
	if (isset($_['nextpage'])) : ?>
		<nav id="page-nav">
	  		<a href="<?php p($_['nextpage']); ?>"></a>
		</nav>
	<?php endif; ?>	

</div>

