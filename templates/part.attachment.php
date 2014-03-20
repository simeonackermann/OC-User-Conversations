<?php
$attachment = $_['attachment'];
extract($attachment);

switch ( $type ) {
	case 'internal_file':
		$icon_url = OC::$WEBROOT . "/index.php/core/preview.png?x=36&y=36&file=" . urlencode($path);
		echo '<a href="'.$download_url.'"><img src="'.$icon_url.'" />'.$name.'</a>';
		break;
	
	case 'internal_image':
		$icon_url = OC::$WEBROOT . "/index.php/core/preview.png?x=200&y=200&file=" . urlencode($path);
		echo '<a href="'.$download_url.'" alt="'.$name.'" title="'.$name.'"><img src="'.$icon_url.'" /></a>';
		//echo '<a href="/owncloud/index.php/apps/gallery/ajax/image.php?file=simi%2Fphotos%2Fparis_neu.jpg" alt="'.$name.'" title="'.$name.'"><img src="/owncloud/index.php/apps/gallery/ajax/thumbnail.php?file=simi%2Fphotos%2Fparis_neu.jpg" /></a>';
		break;

	case 'external_file':
		# code...
		break;

	case 'external_image':
		# code...
		break;

	default:
		# code...
		break;
} ?>