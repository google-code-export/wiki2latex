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

$w2lExtStringFunctions = new ExtStringFunctions ();

$w2lParserFunctions['len']     = array(&$w2lExtStringFunctions, 'runLen' );
$w2lParserFunctions['pos']     = array(&$w2lExtStringFunctions, 'runPos');
$w2lParserFunctions['rpos']    = array(&$w2lExtStringFunctions, 'runRPos');
$w2lParserFunctions['sub']     = array(&$w2lExtStringFunctions, 'runSub');
$w2lParserFunctions['pad']     = array(&$w2lExtStringFunctions, 'runPad');
$w2lParserFunctions['replace'] = array(&$w2lExtStringFunctions, 'runReplace');
$w2lParserFunctions['explode'] = array(&$w2lExtStringFunctions, 'runExplode');

/* Leaving them out this time...
    $wgParser->setFunctionHook('urlencode',array(&$wgExtStringFunctions,'runUrlEncode'));
    $wgParser->setFunctionHook('urldecode',array(&$wgExtStringFunctions,'runUrlDecode'));
*/
