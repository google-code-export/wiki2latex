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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lMathptmxForm';
$wgHooks['w2lFinish'][]               = 'w2lMathptmxHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lMathptmx';

function w2lMathptmx(&$core) {
	$core->addParserParameter('use_mathptmx');
	return true;
}

function w2lMathptmxHook( &$parser, &$text) {

	if ( $parser->getVal('use_mathptmx') ) {
		$parser->addPackageDependency('mathptmx');
	}
	return true;
}

function w2lMathptmxForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="use_mathptmx" value="true" /> ';
	//$output .= wfMsg('w2l_select_mathptmx').'</label><br />'."\n";
	$output .= ' Use Mathptmx (Times New Roman Font)</label><br />'."\n";
	return true;
}


