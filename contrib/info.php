<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Adds some information at the end of an article (as LaTeX-comment)
 */
 
 if ( !defined('MEDIAWIKI') ) {
	$msg  = 'To install Wiki2LaTeX, put the following line in LocalSettings.php:<br/>';
	$msg .= '<tt>require_once( $IP."/extensions/path_to_Wiki2LaTeX_files/wiki2latex.php" );</tt>';
	echo $msg;
	exit( 1 );
}
 
$wgHooks['w2lFinish'][]               = 'w2lInfoHook';

function w2lInfoHook(&$parser, &$text)
{
    $info  = "\n";
    $info .= '%'."\n";
    $info .= '% Wiki2LaTeX infobox'."\n";
    $info .= '% source article: '.($parser->mTitle)."\n";
    $info .= '% generated at: '.date('H:i:s d-m-Y')."\n";
    $info .= '% Wiki2LaTeX Version: '.W2L_VERSION."\n";
    $info .= '%'."\n";
    $info .= "\n";

    /*final copy*/    
    $text = $info.$text;
    
    return true;
}


