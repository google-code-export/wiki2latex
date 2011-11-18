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
 
if ( !function_exists('w2lGallery') ) {

	$w2lTags['source'] = 'w2lGallery';

	function w2lGallery($input, $argv, $parser, $frame = false, $mode = 'latex') {

		// $input should be a list of Images like this:
		// http://www.mediawiki.org/wiki/Help:Images#Rendering_a_gallery_of_images
	
		$parser->addPackageDependency('graphicx');
		
		$parts = explode("\n", $input, 2);
		
		foreach ($parts as $part ) {
			// check for caption...
			
			// get filepath
			
			// make LaTeX-Code
		}
		$output = $input;

		return $output;
	}
}
