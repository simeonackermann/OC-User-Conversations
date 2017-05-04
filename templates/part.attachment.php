<?php
$attachment = $_['attachment'];
extract($attachment);

switch ( $type ) {
	case 'internal_file':
		echo '<a href="'.$download_url.'"><img src="'.$icon_url.'" />'.$name.'</a>';
		break;
	
	case 'internal_image':
		echo '<a href="'.$download_url.'" alt="'.$name.'" title="'.$name.'"><img src="'.$icon_url.'" /></a>';
		break;

	default:
		echo '<a href="'.$download_url.'"><img src="'.$icon_url.'" />'.$name.'</a>';
		break;
}
?>