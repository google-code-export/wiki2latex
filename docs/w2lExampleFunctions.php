<?php

/*
 * File:    w2lExampleFunctions.php
 * Created: 2011-02-22
 * Version: 0.12
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

if ( !defined('MEDIAWIKI') )
	die();

$w2lConfig['div']['exampleclass1'] = array('callback' => 'w2lExampleCallback');
$w2lConfig['div']['exampleclass2'] = array('string' => 'One, two three: %content% Ok, works.', 'filter' => 'w2lExampleFilter'); // You can use %content% as a placeholder for the content of the tag
$w2lConfig['div']['exampleclass3'] = array('environment' => 'exampleenvironment', 'filter' => 'w2lExampleFilter');
$w2lConfig['div']['exampleclass4'] = array('before' => 'Start: ', 'after' => ' End.', 'filter' => 'w2lExampleFilter');

// An example Filter for span/div support
if ( !function_exists('w2lExampleFilter') ) {
	function w2lExampleFilter(&$parser, $content, $tag, $classes) {
		// This function should return the LaTeX-Code, that this class should be
		// transformed to.
		return strtoupper($content);
	}
}

// An example Callback for span/div support
if ( !function_exists('w2lExampleCallback') ) {
	function w2lExampleCallback(&$parser, $content, $tag, $classes, $full_block) {
		// This function should return the LaTeX-Code, that this class should be
		// transformed to.
		return strtoupper($content);

	}
}

// An example to add an XML-style parser tag:
if (!function_exists(example_w2l_tag) {
	
	$wgHooks['w2lInitParser']         = "registerExampleToW2L";
	$wgHooks['ParserFirstCallInit'][] = "registerExampleToMediawiki";

	function registerExampleToW2L(&$parser) {
		global $w2lTags;
		$w2lTags['example'] = "example_w2l_tag";
		return true;
	}

	// If you're adding compatibility to an existing Mediawiki-extension
	// You can skip this function...
	function registerExampleToMediawiki(&$parser) {
		$parser->setHook("example", "example_w2l_tag");
		return true;
	}



	function example_w2l_tag($input, $argv, $parser, $frame = false, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				return 'This is an example output seen in \LaTeX.';
			break;
			case 'wiki':
				return 'This is an exampleoutput as seen when parsed by Mediawiki.';
			break;
			default:
				return 'Well, this should never happen.';
			break;
		}  
	}
}
