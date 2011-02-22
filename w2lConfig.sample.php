<?php

/*
 * File:    w2lConfig(.sample).php
 * Created: 2007-03-02
 * Version: 0.9
 *
 * Purpose:
 * This file contains all the installation-specific overrides of defaultsettings.
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

/* Add your own configuration here */


/* These are some additional features which can be activated by uncommenting the line */
/* Additional Syntax-Features */
// include('contrib/komascript.php');     // Uses komascript in any possible situation
include('contrib/linkify.php');           // Internal Links are created
// include('contrib/paralist.php');       // Use the paralist-package
// include('contrib/showCategories.php'); // Show categories of the page

/* Mediawiki-Extensions */
// include('contrib/math.php');            // Adds math-support
// include('contrib/ParserFunctions.php'); // Adds ParserFunctions
// include('contrib/ref.php');             // Adds citation-support (<ref></ref>)
// include('contrib/source.php');          // Adds source-tag
// include('contrib/StringFunctions.php'); // Adds StringFunctions

/* Additional Font Selectors For The Magic-Template */
//include('contrib/avant.php');    // Font
//include('contrib/bookman.php');  // Font
//include('contrib/helvet.php');   // Font
//include('contrib/lmodern.php');  // Font
//include('contrib/mathpazo.php'); // Font
//include('contrib/mathptmx.php'); // Font

/* Various Options For The Magic-Template */
//include('contrib/frontmatter.php');     // Adds a frontpage
//include('contrib/microtype.php');       // Adds microtype extensions
//include('contrib/tableofcontents.php'); // Adds a table of contents


// These four possibilities show how to use div and span support:
/* $w2lConfig['div']['w2l-mycommand'] = array (
	'before' => '\mycommand{',
	'after'  => '}',
	'filter' => 'w2lMycommandFilter' // this php function gets the whole content of the tag
);*/

/*
$w2lConfig['div']['w2l-remove'] = array (
	'callback' => 'w2lRemove' // this callback function is called by preg_replace_callback
);*/

// Using an empty string removes the div or span box completely:
/*$w2lConfig['div']['w2l-remove'] = array (
	'string' => '', // You can use %content% as a placeholder for the content of the tag
	'filter' => '' // optionally: filter %content%
);*/

/*$w2lConfig['div']['w2l-speciallist'] = array (
	'environment' => 'itemize', //this environment is created
	'filter' => 'transformToList' // optionally: filter content, and create optional settings for environment
);*/
