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

/* Braucht:
* Hook für Magic-Template
* ParserExtensionTag: <index>, <printindex>
* 

*/

/*
 * File:    w2lTags.php
 * Created: 2007-09-01
 * Version: 0.9
 *
 * Purpose:
 * Provides some xml-style Parser-Extension-Tags to Mediawiki and W2L
 *
 * License:
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if ( !defined('MEDIAWIKI') ) {
	$msg  = 'To install Wiki2LaTeX, put the following line in LocalSettings.php:<br/>';
	$msg .= '<tt>require_once( $IP."/extensions/path_to_Wiki2LaTeX_files/wiki2latex.php" );</tt>';
	echo $msg;
	exit( 1 );
}

if ( !defined('MEDIAWIKI') )
	die();

// Adds makeidx-support

class w2lMakeidx {
	function Setup() {
		global $wgParser, $w2lTags;
		// Register Extension-Tags to Mediawiki...
		
		// LaTeX-commands, which we want to offer to a wiki-article
		$wgParser->setHook("index",      array($this, "Index"));
		$wgParser->setHook("printindex", array($this, "PrintIndex"));

		// Some default ones
		
		// Some Extensions, which return LaTeX-commands
		$w2lTags['index']      = array($this, 'Index');
		$w2lTags['printindex'] = array($this, 'PrintIndex');

	}

	function Index($input, $argv, &$parser, $mode = 'wiki') {
		switch ($mode) {
			case 'wiki':
				$output = $input."<sup>(i)</sup>";
			break;
			case 'latex':
				$ind_entry = ( isset($argv['as']) ) ? $argv['as'] : $input;// $input;
				$output    = $input.'\index{'.$ind_entry.'}';
				
				$parser->addPackageDependency('makeidx');
				$parser->addLatexHeadCode('\makeindex');
				$parser->requireSorting();
			break;
			default: $output = $mode;
		}
		return $output;
	}

	function PrintIndex($input, $argv, &$parser, $mode = 'wiki') {

		switch ($mode) {
			case 'wiki':  $output = "(INDEX)";
			break;
			case 'latex': $output = '\printindex'."\n";
			break;
			default: $output = $mode;
		}
		return $output;
	}

}

$w2lMakeidx = new w2lMakeidx();
$wgExtensionFunctions[] = array(&$w2lMakeidx, 'Setup');

