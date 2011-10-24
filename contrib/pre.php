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

if ( !function_exists('w2lPre') ) {

	$w2lTags['pre'] = 'w2lPre';

	function w2lPre($input, $argv, $parser, $frame = false, $mode = 'wiki') {
		//$input = $parser->recursiveTagParse($input);
		$output  = "\n\begin{verbatim}\n";
		$output .= trim($input)."\n";
		$output .= "\end{verbatim}\n";
		return $output;
	}
}
