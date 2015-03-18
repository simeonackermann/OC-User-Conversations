<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/** @var $this OC\Route\Router */
$this->create('conversations_index', '/')
	->actionInclude('conversations/index.php');

$this->create('conversations_ajax_attachmentPreview', 'ajax/attachmentPreview.php')
	->actionInclude('conversations/ajax/attachmentPreview.php');

$this->create('conversations_ajax_deleteComment', 'ajax/deleteComment.php')
	->actionInclude('conversations/ajax/deleteComment.php');

$this->create('conversations_ajax_fetchConversation', 'ajax/fetchConversation.php')
	->actionInclude('conversations/ajax/fetchConversation.php');

$this->create('conversations_ajax_getNavigationIcon', 'ajax/getNavigationIcon.php')
	->actionInclude('conversations/ajax/getNavigationIcon.php');

$this->create('conversations_ajax_newComment', 'ajax/newComment.php')
	->actionInclude('conversations/ajax/newComment.php');

$this->create('conversations_ajax_polling', 'ajax/polling.php')
	->actionInclude('conversations/ajax/polling.php');

$this->create('conversations_ajax_thumbnail', 'ajax/thumbnail.php')
	->actionInclude('conversations/ajax/thumbnail.php');