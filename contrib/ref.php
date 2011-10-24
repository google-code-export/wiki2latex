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
 
if ( !function_exists('w2lRef') ) {

	$w2lTags['ref'] = 'w2lRef';

	function w2lRef($input, $argv, $parser, $frame = false, $mode = 'latex') {
		$command = 'footnote';
		$input   = trim($input);
	 	$input   = $parser->recursiveTagParse($input);
		wfRunHooks('w2lRefTag', array( &$parser, &$command, &$input ) );
		return '\\'.$command.'{'.trim($input).'}';
	}
}

if ( !function_exists('w2lReferences') ) {

	$w2lTags['references']  = 'w2lReferences';

	function w2lReferences($input, $argv, $parser, $frame = false, $mode = 'latex') {
		$output = '';
		wfRunHooks('w2lReferencesTag', array(&$parser, &$input, &$output, &$argv));
		return $output;
	}
}
