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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lTableNoIndentForm';
$wgHooks['w2lTableHead'][]            = 'w2lTableNoIndentHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lTableNoIndent';

function w2lTableNoIndent(&$core) {
	$core->addParserParameter('table_noindent');
	return true;
}

function w2lTableNoIndentHook( &$parser, &$table_head) {

	if ( $parser->getVal('table_noindent') ) {
		$table_head = '\noindent'."\n".$table_head;
	}
	return true;
}

function w2lTableNoIndentForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="table_noindent" checked="checked" value="true" /> ';
	//$output .= wfMsg('w2l_select_avant').'</label><br />'."\n";
	$output .= ' Remove indentation of tables.</label><br />'."\n";
	return true;
}


