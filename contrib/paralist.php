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
 
$wgHooks['w2lFormOptions'][]     = 'w2lParalistForm';
$wgHooks['w2lParseLists'][]      = 'w2lParalistEnv';
$wgHooks['w2lRegisterOptions'][] = 'w2lParalist';

function w2lParalist(&$core) {
	$core->addParserParameter('use_paralist');
	return true;
}

function w2lParalistEnv(&$parser, &$ul, &$ol) {

	if ( $parser->getVal('use_paralist') ) {
		$ul = 'compactitem';
		$ol = 'compactenum';
		$parser->addPackageDependency('paralist');
	}
	return true;
}

function w2lParalistForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="use_paralist" value="true" /> ';
	$output .= wfMsg('w2l_select_paralist').'</label><br />'."\n";
	return true;
}


