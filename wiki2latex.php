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

define('W2L_VERSION', '13: 2013-02-13');

define('NS_WIKI2LATEX', 400);
define('NS_WIKI2LATEX_TALK', 401);

$wgExtraNamespaces[NS_WIKI2LATEX] = 'Wiki2LaTeX';
$wgExtraNamespaces[NS_WIKI2LATEX_TALK] = 'Wiki2LaTeX_talk';

$w2lConfig          = array();
$w2lTags            = array();
$w2lParserFunctions = array();

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

$wgHooks['SkinTemplateContentActions'][] = array(&$w2lHelper);
$wgHooks['SkinTemplateNavigation'][]     = array(&$w2lHelper);
$wgHooks['UnknownAction'][]              = array(&$w2lHelper);
$wgHooks['BeforePageDisplay'][]          = array(&$w2lHelper);
$wgHooks['GetPreferences'][]             = array(&$w2lHelper);
$wgHooks['ParserFirstCallInit'][]        = array(&$w2lExtensionTags, 'Setup');

// Internal usage of hooks
$wgHooks['w2lInitParser'][] =  array(&$w2lExtensionTags, 'w2lSetup'); //"Wiki2LaTeXTags::w2lSetup"

$wgExtensionMessagesFiles['wiki2latex']  = dirname( __FILE__ ) . '/w2lMessages.php';

$wgExtensionFunctions[] = array(&$w2lHelper, 'Setup');



