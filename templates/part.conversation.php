<?php if ( empty($_['conversation'])) 
	//echo "<i>No comments to load.</i>";
?>
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
				<?php p( $author ); ?>
			</div>			
			<?php 
			$uid = OC_User::getUser();
			if( USER_CONVERSATIONS_CAN_DELETE && ( OC_User::isAdminUser($uid) || $author == $uid ) ): ?>
				<div class="delete"><a href="#" class="action delete delete-icon"></a></div>
			<?php endif; ?>
			<div class="date">
				<?php p($l->t($date["text"], $date["val"])); ?>
			</div>

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