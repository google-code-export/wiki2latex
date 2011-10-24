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
 
if ( !function_exists('w2lMath') ) {

	$w2lTags['math'] = 'w2lMath';
	$wgHooks['w2lBeginParse'][] = 'w2lDoDisplayMath';

	function w2lMath($input, $argv, $parser, $frame = false, $mode = 'latex') {
	
	if ( isset($argv['style']) && $argv['style'] == 'display' ) {
		$output  = "\n\begin{equation}\n";
		$output .= trim($input)."\n";
		$output .= "\end{equation}\n";
	} else {
		$output  = "\n\begin{math}\n";
		$output .= trim($input)."\n";
		$output .= "\end{math}\n";
	}
	return $output;

	}

	function w2lDoDisplayMath(&$parser, &$text) {
		$text = str_replace(":<math>", "<math style=\"display\">", $text);
		return true;
	}

}
