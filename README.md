# User Conversations #

This app provides a simple and clean possibility to have conversations/discussions between users.

AThe conversations are divided into the groups of the users if any available.

Alpha-feature: file attachments (yet deactivated by default). Can activated in appinfo/app.php. Use it with care.

## Features: 

 * currently only clean messages (no bbcode etc)
 * rooms for user groups
 * ajax polling
 * global new message detection
 * includes profile picture


## Install:
 * download and unzip to apps/conversations folder
 * activate it in oc backend

Attention: this is beta software.


## TODO 

* BUG: when changing/adding user to group the "activeRoom" user value evoid the change -> need a hook for this case, deleting the user value
* integrate UserTimezone
* language sensitive date (after x days) if no userValue setted
* add attachments (file sharings):
	* Error test: isFile, on print yet shared?
	* real internal_image (not y square)
	* use: OC_Helper::previewIcon( $path ); OC_Helper::mimetypeIcon( $mimetype );
	* Share-Bug: wrong download-link for items in a shared subfolder (e.g. /Music/SharedMusic/File.mp3)
* user/admin can delete messages
* highlight new messages
* Link/URL triggering
	url detection:http://www.9lessons.info/2010/06/facebook-like-extracting-url-data-with.html
    Facebook url detection: http://www.youtube.com/watch?v=twqWe327yyE
* wysiwyg (fett,italic,...)

## Changelog:

Version 0.1.5 / 2014/03/15
 * autosize textarea
 * French translation
 * Alpha-feature: file attachments 

 Version 0.1.2 / 2014/03/02
 * fixed CSS app-content overlapped by rooms list

 Version 0.1.1 / 2014/03/02
 * fixed PHP function return write access

 Version 0.1 / 2014/03/01
 * Initial release