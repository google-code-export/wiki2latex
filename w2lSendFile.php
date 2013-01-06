<?php

/*
 * File:    w2lSendFile.php
 * Created: 2007-09-01
 * Version: 0.9
 *
 * Purpose:
 * Sends the generated pdf-file to the browser
 *
 * License:
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

define('W2L_SENDFILE', 1);


// Some Config-Hacks to allow for including some original Mediawiki-files:
define('MEDIAWIKI', 1);

require_once("../../includes/GlobalFunctions.php");

require_once("w2lFunctions.php");

$file  = addslashes( $_GET['fid'] );
$fmt   = addslashes( $_GET['fmt'] );
$title = addslashes( $_GET['title'] );

$tmp = w2lTempDir();

$title = w2lWebsafeTitle($title);

$file_loc = $tmp.DIRECTORY_SEPARATOR.'w2ltmp-'.$file.DIRECTORY_SEPARATOR.'Main.'.$fmt;

switch ( $fmt ) {
	case 'pdf': $mime_type = 'application/pdf';
	break;
	case 'tex': $mime_type = 'application/x-tex';
	break;
	default:    $mime_type = 'text/plain';
	break;
}
if ( file_exists($file_loc) ) {

	header("Content-Type: ".$mime_type);
	// Es wird downloaded.pdf benannt
	header('Content-Disposition: attachment; filename="'.$title.'.'.$fmt.'"');
	readfile($file_loc);
} else {
	echo 'damn! Error receiving '.$file_loc;
}

