<?php

/*
 * File:    w2lFunctions.php
 * Created: 2007-09-01
 * Version: 0.9
 *
 * Purpose:
 * Contains some function, which are needed in various contexts.
 * Especially when there are not all functions of MW or W2L loaded
 *
 * License:
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
 
if ( defined('W2L_SENDFILE') ) {
	// a small function from mediawiki needed in sendfile.php
	function wfTempDir() {
		foreach( array( 'TMPDIR', 'TMP', 'TEMP' ) as $var ) {
			$tmp = getenv( $var );
			if( $tmp && file_exists( $tmp ) && is_dir( $tmp ) && is_writable( $tmp ) ) {
				return $tmp;
			}
		}
		# Hope this is Unix of some kind!
		return '/tmp';
	}
}

if ( !function_exists('w2lWebsafeTitle') ) {
	function w2lWebsafeTitle($title) {
		$file_saver = array(
			"/", "&", "%", "$", ",", ";", ":", "!", "?","*", "#", "'", '"', "´", "`", "+", "\\", " "
		);
		$title = str_replace(array_values($file_saver), '_', $title);
		$title = substr($title, 0, 100);
		return $title;
	}
}

if ( !function_exists('w2lExampleFilter') ) {
	function w2lExampleFilter(&$parser, $content, $tag, $classes) {
		// This function should return the LaTeX-Code, that this class should be
		// transformed to.
		return strtoupper($content);
	}
}

function w2lExampleCallback(&$parser, $content, $tag, $classes, $full_block) {
	// This function should return the LaTeX-Code, that this class should be
	// transformed to.
	return strtoupper($content);

}
