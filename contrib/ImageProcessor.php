<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 */
 
 if ( !defined('MEDIAWIKI') ) {
	$msg  = 'To install Wiki2LaTeX, put the following line in LocalSettings.php:<br/>';
	$msg .= '<tt>require_once( $IP."/extensions/path_to_Wiki2LaTeX_files/wiki2latex.php" );</tt>';
	echo $msg;
	exit( 1 );
}

// Nothing here yet.. sorry
// This function demonstrates, how image parsing can be optimized in v.0.9
$wgHooks['w2lImage'][] = 'w2lImageProcessor';

function w2lImageProcessor(&$parser, &$file, &$graphic_package, &$graphic_command, &$imagepath, &$imagename, &$imgwith, &$caption) {
	// we simply analyze imagename and use a special latex-command, if we do know about a prefix which we find.
	return true;
}


