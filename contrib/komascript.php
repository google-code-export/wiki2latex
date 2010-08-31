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
 
// Config values:

// Sign up to some Hooks
$wgHooks['w2lFormOptions'][]     = 'w2lKomascriptForm';
$wgHooks['w2lFinish'][]          = 'w2lKomascriptHook';
$wgHooks['w2lRegisterOptions'][] = 'w2lKomascript';
$wgHooks['w2lHeadings'][]        = 'w2lKomascriptHeadings';
$wgHooks['w2lMagicTemplateCreate'][] = 'w2lKomascriptMagicTemplate';

function w2lKomascript(&$core) {
	$core->addParserParameter('use_komascript');
	return true;
}

function w2lKomascriptHook( &$parser, &$text ) {

	if ( $parser->getVal('use_komascript') ) {
		$docClass = $parser->getVal('documentclass');
		$change_class = array(
			'article' => 'scrartcl',
			'report'  => 'scrreprt',
			'book'    => 'scrbook'
		);
		$docClass = str_replace(array_keys($change_class), array_values($change_class), $docClass );
		$parser->setVal('documentclass', $docClass);
	}
	return true;
}

function w2lKomascriptForm( &$core, &$output ) { 
	$output .= '<label><input type="checkbox" name="use_komascript" value="true" checked="checked" /> ';
	//$output .= wfMsg('w2l_select_komascript').'</label><br />'."\n";
	$output .= ' Use Komascript</label><br />'."\n";
	return true;
}

function w2lKomascriptHeadings( &$parser, &$heading, &$level, &$heading_command) {
	if ( $parser->getVal('use_komascript') ) {
		$headings_latex_koma = array('addpar', 'addchap', 'addsec',  'subsection', 'subsubsection', 'paragraph', 'subparagraph');
		
		if ( substr($heading, 0, 3) == '***' ) {
		  // ***
		  $heading = trim(substr($heading, 3));
		  $heading_command = $headings_latex_koma[$level].$parser->sc['asteriks'];
		} elseif ( substr($heading, 0, 2) == '**' ) {
		  // **
		  $heading = trim(substr($heading, 2));
		  $heading_command = $headings_latex_koma[$level];
		}
	}

	return true;
}

function w2lKomascriptMagicTemplate( &$core, &$docClassOption) {
	if ( $core->Parser->getVal('use_komascript') ) {
		$docClassOption = 'paper=a4,fontsize=12pt';
	}
	return true;
}

