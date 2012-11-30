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

// Hooking our function into Mediawiki-Hook:
//w2lBeforeExtractTags
$wgHooks['w2lBeforeExtractTags'][] = 'w2lDoMathJax';

$w2lMathjax = array();
$w2lMathjax['dollardollar'] = array(
	"mj_start"  => '$$',
	"mj_end"    => '$$',
	"ltx_start" => '$$',
	"ltx_end"   => '$$',
	"active"    => true
);
$w2lMathjax['dollar'] = array(
	"mj_start"  => '$',
	"mj_end"    => '$',
	"ltx_start" => '$',
	"ltx_end"   => '$',
	"active"    => false
);
$w2lMathjax['parentheses'] = array(
	"mj_start"  => '\(',
	"mj_end"    => '\)',
	"ltx_start" => '\(',
	"ltx_end"   => '\)',
	"active"    => true
);
$w2lMathjax['indented_math'] = array(
	"mj_start"  => ':<math>',
	"mj_end"    => '</math>',
	"ltx_start" => '\begin{equation}',
	"ltx_end"   => '\end{equation}',
	"active"    => true
);
$w2lMathjax['math'] = array(
	"mj_start"  => '<math>',
	"mj_end"    => '</math>',
	"ltx_start" => '\begin{math}',
	"ltx_end"   => '\end{math}',
	"active"    => true
);

$w2lMathjax['squarebrackets'] = array(
	"mj_start"  => '\[',
	"mj_end"    => '\]',
	"ltx_start" => '\[',
	"ltx_end"   => '\]',
	"active"    => true
);
/*
// This syntax requires special treatment :(
$w2lMathjax_comp['env'] = array(
	"mj_start"  => '\begin{}',
	"mj_end"    => '\end{}',
	"ltx_start" => '',
	"ltx_end"   => ''
);
*/

// Checks, if chances are good, that there is a valid mathjaxtag in text
function w2lMathJaxCheckFor($text, $mathjax) {

	$pos_opener = strpos($text, $mathjax['mj_start']);
	$pos_close  = strpos($text, $mathjax['mj_end'], $pos_opener);

	if ( $pos_opener === false )  {
		return false;
	}
	if ( $pos_close === false )  {
		return false;
	}
	return true;
}

function w2lDoMathJax(&$parser, &$article) {
	// This function takes the whole mediawiki-article before any mediawiki-formatting has been processed.
	// Exception: templates/parser-functions have been processed...

	global $w2lMathjax;
	$text = $article;
	
	// Let's check, if MathJax is disabled:
	if ( $parser->hasDirective('__NOMATHJAX__') ) {
		$parser->debugMessage('MathJax-Main', 'Directive: __NOMATHJAX__ detected!');
		return true;
	}
	// At first we should take out nomathjax-parts

	// Now go for the mathjax-part
	
	foreach( $w2lMathjax as $type => $mathjax_syntax ) {
		
		if ( $mathjax_syntax['active'] === false ) {
			switch($type) {
				case "dollar":
					if ( $parser->hasDirective('__MATHJAX_DOLLAR__') !== true ) {
						continue 2;
					}
				break;
				case "dollardollar":
					if ( $parser->hasDirective('__MATHJAX_DOLLARDOLLAR__') !== true ) {
						continue 2;
					}
				break;
				default:
					// Active is false, no special treatment, so cancel
					continue 2;
				break;
			}
		} else {
			switch($type) {
				case "dollar":
					if ( $parser->hasDirective('__MATHJAX_NODOLLAR__') === true ) {
						continue 2;
					}
				break;
				case "dollardollar":
					if ( $parser->hasDirective('__MATHJAX_NODOLLARDOLLAR__') === true ) {
						$parser->debugMessage('MathJax-Main', 'Directive: __MATHJAX_NODOLLARDOLLAR__ detected!');
						continue 2;
					}
				break;
				default:
					// Active is true, no special treatment
				break;
			}
		}

		$cont = w2lMathJaxCheckFor($text, $mathjax_syntax);
		while( $cont !== false ) {
			if ( strpos($text, $mathjax_syntax['mj_start']) !== false ) {
				// the opener is in it, so let's see, what we can do...
				$preface = explode($mathjax_syntax['mj_start'], $text, 2);
				$tail    = explode($mathjax_syntax['mj_end'],   $preface[1], 2);
	
				$math_part = $mathjax_syntax['ltx_start'].$tail[0].$mathjax_syntax['ltx_end'];
				$marker = $parser->getMarkerFor($math_part, 'MATHJAX');
				
				$text = $preface[0].$marker.$tail[1];
			}
			$cont = w2lMathJaxCheckFor($text, $mathjax_syntax);
			
		}

	}
	$article = $text;
	// Reinsert no-mathjax-parts:

	return true;
}
