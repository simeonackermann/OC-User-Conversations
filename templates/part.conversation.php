<?php foreach ($_['conversation'] as $post) : 
	$post = OC_Conversations::preparePost($post);
	extract($post); ?>
	<div class="comment" data-id="<?php p($id); ?>">
		<div class="comment-header">
			<?php if ( ! empty($avatar) ) { ?>
				<div class="avatar">
					<img src="<?php p($avatar); ?>" />
				</div>
			<?php } ?>
			<div class="author">
				<strong><?php p(OC_User::getDisplayName($author)); ?></strong>				
			</div>
			<div class="date">
				<time class="timeago" datetime="<?php p($date['ISO8601']); ?>"><?php p($date['datetime']); ?></time>
			</div>
			<?php 
			$uid = OC_User::getUser();
			if( USER_CONVERSATIONS_CAN_DELETE && ( OC_User::isAdminUser($uid) || $author == $uid ) ): ?>
				<div class="delete"><a href="#" class="action delete delete-icon icon-delete"></a></div>
			<?php endif; ?>
		</div>
		<?php if ( ! empty($text) ) { ?>
			<div class="comment-text">
				<?php print_unescaped( $text ); ?>
			</div>
		<?php } 
		if ( ! empty($attachment) ) { ?>
			<div class="comment-attachment">
				<?php echo $this->inc('part.attachment', array('attachment' => $attachment)); ?>
			</div>
		<?php } ?>
	</div>
<?php endforeach; ?>