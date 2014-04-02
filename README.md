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

* on new messages or polling -> append on top to current conversation, dont load the complete stream!
	.simple solution 1: get every message with higher IDs the current max id...
* integrate UserTimezone
* language sensitive date (after x days) if no userValue setted
* add attachments (file sharings):
	* real internal_image (not y square) -> OC_Image
* remove red new message app-icon in app-menu when conversations app clicked
* highlight new messages -> http://jqueryui.com/effect/
* Link/URL triggering
	url detection:http://www.9lessons.info/2010/06/facebook-like-extracting-url-data-with.html
    Facebook url detection: http://www.youtube.com/watch?v=twqWe327yyE
* wysiwyg (fett,italic,...)
* emoticons
* option: message to single user
* config menu in control header bar for special features
* config-option: dont split into rooms/groups
* code documentation

## Changelog:

Version 0.1.6 / 2014/04/03
* user can delete own posts, admin all
* test if attached internal file exists
* making hyperlinks clickable
* loader icon on new post submit
* some bug fixes and codechanges

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