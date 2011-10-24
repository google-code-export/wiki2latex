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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lLModernForm';
$wgHooks['w2lFinish'][]               = 'w2lLModernHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lLModern';

function w2lLModern(&$core) {
	$core->addParserParameter('use_lmodern');
	return true;
}

function w2lLModernHook( &$parser, &$text ) {

	if ( $parser->getVal('use_lmodern') ) {
		$parser->addPackageDependency('lmodern');
	}
	return true;
}

function w2lLModernForm(&$output, &$core) { 
	$output .= '<label><input type="checkbox" name="use_lmodern" value="true" /> ';
	//$output .= wfMsg('w2l_select_mathpazo').'</label><br />'."\n";
	$output .= ' Use Latin Modern (lmodern)</label><br />'."\n";
	return true;
}


