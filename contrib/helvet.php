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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lHelvetForm';
$wgHooks['w2lFinish'][]               = 'w2lHelvetHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lHelvet';

function w2lHelvet(&$core) {
	$core->addParserParameter('use_helvet');
	return true;
}

function w2lHelvetHook( &$parser, &$text) {

	if ( $parser->getVal('use_helvet') ) {
		$parser->addPackageDependency('helvet');
	}
	return true;
}

function w2lHelvetForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="use_helvet" value="true" /> ';
	//$output .= wfMsg('w2l_select_helvet').'</label><br />'."\n";
	$output .= ' Use Helvet</label><br />'."\n";
	return true;
}


