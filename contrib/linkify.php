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

$wgHooks['w2lInternalLinks'][]  = 'w2lLinkifyInternalLinks';
$wgHooks['w2lInterwikiLinks'][] = 'w2lLinkifyInterwikiLinks';

function w2lLinkifyInternalLinks(&$parser, &$link, &$linktext, &$linked_page) {
	$hr_options = '';
	wfRunHooks('w2lNeedHyperref', array(&$parser, &$hr_options) );
	$parser->addPackageDependency('hyperref', $hr_options);

	$target = Title::newFromText($linked_page);
	$target_url = $target->escapeFullURL();
	
	$link = $parser->maskURL($target_url, $linktext);
	
	return true;
}

function w2lLinkifyInterwikiLinks(&$parser, &$url, &$linktext, &$command) {
	$hr_options = '';
	wfRunHooks('w2lNeedHyperref', array(&$parser, &$hr_options) );
	$parser->addPackageDependency('hyperref', $hr_options);
	
	$command = $parser->maskURL($url, $linktext);

	return true;
}
