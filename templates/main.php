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

