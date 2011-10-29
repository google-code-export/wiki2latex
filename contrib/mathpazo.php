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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lMathpazoForm';
$wgHooks['w2lFinish'][]               = 'w2lMathpazoHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lMathpazo';

$wgHooks['GetPreferences'][]    = 'w2lMathPazoPreferences';

function w2lMathpazo(&$core) {
	$core->addParserParameter('use_mathpazo');
	return true;
}

function w2lMathpazoHook( &$parser, &$text) {

	if ( $parser->getVal('use_mathpazo') ) {
		$parser->addPackageDependency('mathpazo');
	}
	return true;
}

function w2lMathpazoForm( &$core, &$output ) {
	global $wgUser;
	if ( $wgUser->getOption('w2lMathPazoDefault') == true ) {
		$output .= '<label><input type="checkbox" name="use_mathpazo" value="true" checked="checked" /> ';
	} else {
		$output .= '<label><input type="checkbox" name="use_mathpazo" value="true" /> ';
	}
	$output .= ' Use MathPazo</label><br />'."\n";
	
	return true;
}

function w2lMathPazoPreferences ( $user, &$preferences ) {
	wfLoadExtensionMessages( 'wiki2latex' );
	$preferences['w2lMathPazoDefault'] = array(
		'class' => 'HTMLCheckField',
		'label-message' => 'w2l-mathpazo-default', // a system message
		'section' => 'wiki2latex',
	);

 
	return true;
}

