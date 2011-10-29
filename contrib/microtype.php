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
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lMicrotypeForm';
$wgHooks['w2lFinish'][]               = 'w2lMicrotypeHook';
$wgHooks['w2lRegisterOptions'][]      = 'w2lMicrotype';
$wgHooks['GetPreferences'][]    = 'w2lMicrotypePreferences';

function w2lMicrotype(&$core) {
	$core->addParserParameter('use_microtype');
	return true;
}

function w2lMicrotypeHook( &$parser, &$text ) {

	if ( $parser->getVal('use_microtype') ) {
		$parser->addPackageDependency('microtype');
	}
	return true;
}

function w2lMicrotypeForm( &$core, &$output ) {
	global $wgUser;
	if ( $wgUser->getOption('w2lMicrotypeDefault') == true ) {
		$output .= '<label><input type="checkbox" name="use_microtype" value="true" checked="checked" /> ';
	} else {
		$output .= '<label><input type="checkbox" name="use_microtype" value="true" /> ';
	}
	$output .= ' Use Microtype</label><br />'."\n";
	return true;
}


function w2lMicrotypePreferences ( $user, &$preferences ) {
	wfLoadExtensionMessages( 'wiki2latex' );
	$preferences['w2lMicrotypeDefault'] = array(
		'class' => 'HTMLCheckField',
		'label-message' => 'w2l-microtype-default', // a system message
		'section' => 'wiki2latex',
	);

 
	return true;
}
