<?php

/*
 * File: wiki2latex.php
 *
 * Purpose:
 * Registers Wiki2LaTeX to Mediawiki
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

define('W2L_VERSION', 'pre-0.10');

$w2lConfig          = array();
$w2lTags            = array();
$w2lParserFunctions = array();

// for compatibility...
$w2l_config     =& $w2lConfig;
$w2l_tags       =& $w2lTags;
$w2l_pFunctions =& $w2lParserFunctions;

// Require the class-files
require_once('w2lTags.php');
require_once('w2lHelper.php');

// Some functions:
require_once('w2lFunctions.php');

$w2lExtensionTags = new Wiki2LaTeXTags();
$w2lHelper        = new Wiki2LaTeXHelper();

// load config files
require_once('w2lDefaultConfig.php');

if ( file_exists( dirname(__FILE__).'/w2lConfig.php') ) {
	include_once('w2lConfig.php');
}

// Autoload classes
$wgAutoloadClasses['Wiki2LaTeXParser']   = dirname(__FILE__) . '/w2lParser.php';
$wgAutoloadClasses['Wiki2LaTeXCore']     = dirname(__FILE__) . '/w2lCore.php';
$wgAutoloadClasses['Wiki2LaTeXCompiler'] = dirname(__FILE__) . '/w2lLaTeXCompiler.php';

$wgHooks['LoadAllMessages'][]            = array(&$w2lHelper);
$wgHooks['SkinTemplateContentActions'][] = array(&$w2lHelper);
$wgHooks['UnknownAction'][]              = array($w2lHelper);
$wgHooks['BeforePageDisplay'][]          = array(&$w2lHelper);

$wgExtensionMessagesFiles['wiki2latex'] = dirname( __FILE__ ) . '/w2lMessages.php';

$wgExtensionFunctions[] = array(&$w2lHelper, 'Setup');
$wgExtensionFunctions[] = array(&$w2lExtensionTags, 'Setup');


