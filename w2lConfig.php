<?php
/*
 * Created on 02.03.2007
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 */

if ( !defined('MEDIAWIKI') )
	die();

// Here is the place for your personal configuration. Values should be copied
// from w2lDefaultConfig.php.

// register Parserfunctions
// In case your Mediawiki-installation does not use the ParserFunctions-Extension,
// the following lines should be removed or put into a comment.

include('contrib/ref.php');
include('contrib/paralist.php');
include('contrib/microtype.php');
include('contrib/mathpazo.php');
include('contrib/ParserFunctions.php');
include('contrib/komascript.php');

$w2lHgkBibliography = new hgkBibliography;
$w2lParserFunctions['Zitat']    = array( &$w2lHgkBibliography, 'cite' );
$w2lParserFunctions['Eintrag']      = array( &$w2lHgkBibliography, 'item' );
$w2lParserFunctions['Bibliographie']  = array( &$w2lHgkBibliography, 'bibliography' );		
$w2lParserFunctions['Optionen']    = array( &$w2lHgkBibliography, 'options' );
$w2lParserFunctions['use'] = array( &$w2lHgkBibliography, 'useItem');
$w2lTags['loadbib']   = array( &$w2lHgkBibliography, 'LoadBib');



$w2lTags['smallspacing']   = array('hgk_parser_extensions', 'Smallspacing');
$w2lTags['defaultspacing'] = array('hgk_parser_extensions', 'Defaultspacing');
$w2lTags['blb']            = array('hgk_parser_extensions', 'Blb');
$w2lTags['bdsl']           = array('hgk_parser_extensions', 'BDSL');
$w2lTags['ub']             = array('hgk_parser_extensions', 'Ub');
$w2lTags['kgk']            = array('hgk_parser_extensions', 'Kgk');
$w2lTags['whatlinkshere']  = array('hgk_parser_extensions', 'Whatlinkshere');
$w2lTags['sups']           = array('hgk_parser_extensions', 'Sups');
$w2lTags['upper']          = array('hgk_parser_extensions', 'Upper');
$w2lTags['smaller']        = array('hgk_parser_extensions', 'Smaller');
$w2lTags['todo']           = array('hgk_calendar_extension', 'Todo');
$w2lTags['event']          = array('hgk_calendar_extension', 'Event');

$w2lTags['anchor'] = array('w2lExtensions', "Anchor");
$w2lTags['intref'] = array('w2lExtensions', "intRef");


$w2lTags["ilias"] = "Ilias2Teilnahmeliste";

$w2lConfig['defaults'][] = array('search' => 'Hausarbeit', 'template'=>'Hausarbeit', 'action'=>'w2lpdf');
$w2lConfig['defaults'][] = array('search' => 'Handout', 'template'=>'Handout', 'action'=>'w2lpdf');
$w2lConfig['defaults'][] = array('search' => 'Notizen', 'template'=>'Referatsnotizen', 'action'=>'w2lpdf');
$w2lConfig['defaults'][] = array('search' => 'Teilnahmelisten', 'template'=>'Teilnahmelisten', 'action'=>'w2lpdf');

$w2lConfig['allowed_ns'][] = 112;
$w2lConfig['allowed_ns'][] = 102;



$w2lConfig['default_action'] = 'w2lpdf';
$w2lConfig['default_template'] = 'Hausarbeit';

$w2lConfig['babel_default'] = 'ngerman';
