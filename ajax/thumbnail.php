<?php

// Todo: Show Image Thumbnail

/*
// 1. Version: 
$fileView = new \OC\Files\View('/' . $owner . '/files');
$local = $fileView->getLocalFile($img);
header('Content-Type: ' . $mime);
readfile($local);

// 2. Version:
$root = 'files/' . $path;
//$preview = new \OC\Preview($userId, $root);			
//$preview->setFile($sharedFile);

$preview = new \OC\Preview($userId, '/', '/test.txt');
$preview->setMaxX(36);
$preview->setMaxY(36);
$preview->setScalingUp(false);



echo $preview->getFile();
$preview->showPreview();

?>