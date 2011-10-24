<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 */
 
/*
 * To use this extension, include it in w2lConfig.php by adding:
 *
 * include('contrib/frontmatter.php');
 *
 * Order of inclusion matters at the moment, it seems to work correctly
 * if it is included last. Otherwise you might get the TOC before the
 * frontpage for example.
 */ 

 if ( !defined('MEDIAWIKI') ) {
	$msg  = 'To install Wiki2LaTeX, put the following line in LocalSettings.php:<br/>';
	$msg .= '<tt>require_once( $IP."/extensions/path_to_Wiki2LaTeX_files/wiki2latex.php" );</tt>';
	echo $msg;
	exit( 1 );
}
 
$wgHooks['w2lMagicTemplateOptions'][] = 'w2lFrontForm';
$wgHooks['w2lFinish'][]					= 'w2lFrontHook';
$wgHooks['w2lRegisterOptions'][]		= 'w2lFront';

function w2lFront(&$core) {
	$core->addParserParameter('make_front');
	$core->addParserParameter('front_title');
	$core->addParserParameter('front_author');
	$core->addParserParameter('front_date');
	return true;
}

function w2lFrontHook( &$parser, &$text) {

	if ( $parser->getVal('make_front') ) {
		$date	= $parser->getVal('front_date');
		$author = $parser->getVal('front_author');
		$title  = $parser->getVal('front_title');

		$text = '\maketitle'."\n\n".$text;
		if ( $date	) $text = '\date{'.$date."}\n".$text;
		if ( $author ) $text = '\author{'.$author."}\n".$text;
		if ( $title  ) $text = '\title{'.$title."}\n".$text;
	}
	return true;
}

function w2lFrontForm( &$core, &$output ) { 
	$output .= '<style type="text/css">label.frontmatter { float: left; width: 120px; }</style>';
	$output .= '<fieldset><legend>FrontMatter</legend>'."\n";

	$output .= '<label for="make_front" class="frontmatter">Add frontpage</label>';
	$output .= '<input type="checkbox" name="make_front" id="make_front" value="true" /><br />'."\n";

	$output .= '<label for="front_title" class="frontmatter">Title</label>';
	$output .= '<input type="text" name="front_title" id="front_title" value="'.$core->getTitle().'" /><br />'."\n";

	$articledate = preg_replace('/^(.{4})(.{2})(.{2}).{6}$/','$1-$2-$3',$core->getRevisionTimestamp());
	$output .= '<label for="front_date" class="frontmatter">Date</label>';
	$output .= '<input type="text" name="front_date" id="front_date" value="'.$articledate.'" /><br />'."\n";

	$output .= '<label for="front_author" class="frontmatter">Author</label>';
	$output .= '<input type="text" name="front_author" id="front_author" value="" /><br />'."\n";

	$output .= '</fieldset>'."\n";
	return true;
}


