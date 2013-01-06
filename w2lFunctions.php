<?php

/*
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

/**
 * Check if the operating system is Windows
 *
 * @return Bool: true if it's Windows, False otherwise.
 */
	if ( !function_exists('wfIsWindows') ) {

        function wfIsWindows() {
            static $isWindows = null;
            if ( $isWindows === null ) {
                $isWindows = substr( php_uname(), 0, 7 ) == 'Windows';
            }
            return $isWindows;
        }
    }
}

/**
 * Tries to get the system directory for temporary files. The TMPDIR, TMP, and
 * TEMP environment variables are then checked in sequence, and if none are set
 * try sys_get_temp_dir() for PHP >= 5.2.1. All else fails, return /tmp for Unix
 * or C:\Windows\Temp for Windows and hope for the best.
 * It is common to call it with tempnam().
 *
 * NOTE: When possible, use instead the tmpfile() function to create
 * temporary files to avoid race conditions on file creation, etc.
 *
 * This function is from MediaWiki 1.19
 *
 * @return String
 */
function w2lTempDir() {
    foreach( array( 'TMPDIR', 'TMP', 'TEMP' ) as $var ) {
        $tmp = getenv( $var );
        if( $tmp && file_exists( $tmp ) && is_dir( $tmp ) && is_writable( $tmp ) ) {
            return $tmp;
        }
    }
    if ( function_exists( 'sys_get_temp_dir' ) ) {
        return sys_get_temp_dir();
    }
    # Usual defaults
    return wfIsWindows() ? 'C:\Windows\Temp' : '/tmp';
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
