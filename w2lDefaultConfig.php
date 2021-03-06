<?php

/*
 * File: w2lDefaultConfig.php
 *
 * Purpose:
 * Contains default settings. All settings can be changed in w2lConfig.php
 * Do NOT edit this file. Changes in this file get overwritten at any update.
 * Any custom settings should go into w2lConfig.php (or rename w2lConfig.sample.php)
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

// Caution: pdfexport only works by using templates. These are described on the website.
// PDF-Export is used on your own risk.
$w2lConfig['pdfexport'] = true;

// Which Namespaces should show the LaTeX-tab? Add them by their number and seperate them with commas.
$w2lConfig['allowed_ns'] = array(NS_MAIN);

// Is an anonymous user allowed, to view the 'LaTeX-Tab'?
$w2lConfig['allow_anonymous'] = false;

// Adding some default extension-tags:
include_once('contrib/math.php');
include_once('contrib/pre.php');

// Default Values used by form:
$w2lConfig['default_template'] = 'auto';

$w2lConfig['docclass'] = 'article';
$w2lConfig['process_curly_braces'] = '2';

// This command should work. Before your try to enable pdfexport, please try this
// command on your local shell.
$w2lConfig['ltx_command'] = 'pdflatex -interaction=batchmode %file%';
$w2lConfig['ltx_sort']    = 'makeindex %file%'; // unused
$w2lConfig['ltx_bibtex']  = 'bibtex %file%';    // unused
$w2lConfig['ltx_repeat']  = 3;

$w2lConfig['auto_clear_tempfolder'] = false;

$w2lConfig['magic_template'] = 'w2lMagicTemplate.php';

$w2lConfig['div'] = array (  );

$w2lConfig['span'] = array (  );

