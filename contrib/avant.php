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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lAvantForm';
$wgHooks['w2lFinish'][]               = 'w2lAvantHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lAvant';

function w2lAvant(&$core) {
	$core->addParserParameter('use_avant');
	return true;
}

function w2lAvantHook( &$parser, &$text) {

	if ( $parser->getVal('use_avant') ) {
		$parser->addPackageDependency('avant');
	}
	return true;
}

function w2lAvantForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="use_avant" value="true" /> ';
	//$output .= wfMsg('w2l_select_avant').'</label><br />'."\n";
	$output .= ' Use Avant</label><br />'."\n";
	return true;
}


