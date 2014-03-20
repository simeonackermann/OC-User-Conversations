<?php foreach ($_['conversation'] as $post) : 
	$post = OC_Conversations::preparePost($post);
	extract($post);
	?>
	<div class="comment" data-id="<?php p($post['id']); ?>">
		<div class="comment-header">
			<?php if ( ! empty($avatar) ) { ?>
				<div class="avatar">
					<img src="<?php p($avatar); ?>" />
				</div>
			<?php } ?>
			<div class="author">
				<?php p( $author ); ?>
			</div>
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