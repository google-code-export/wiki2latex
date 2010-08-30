<?php

/*
 * File:    w2lTags.php
 * Created: 2007-09-01
 * Version: 0.9
 *
 * Purpose:
 * Provides some xml-style Parser-Extension-Tags to Mediawiki and W2L
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

if ( !defined('MEDIAWIKI') )
	die();

/* W2l-Parser-Extensions */

class Wiki2LaTeXTags {
	function Setup(&$parser) {
		// Register Extension-Tags to Mediawiki...
		
		// LaTeX-commands, which we want to offer to a wiki-article
		$parser->setHook("noindent",    array($this, "NoIndent"));
		$parser->setHook("newpage",     array($this, "NewPage"));
		$parser->setHook("label",       array($this, "Label"));
		$parser->setHook("pageref",     array($this, "PageRef"));
		$parser->setHook("chapref",     array($this, "ChapRef"));
		
		// Extension-tags, which we need for w2l:
		$parser->setHook("templatevar", array($this, "TemplateVar"));
		$parser->setHook("latex",       array($this, "Latex"));
		$parser->setHook("latexpage",   array($this, "LatexPage"));
		$parser->setHook("latexfile",   array($this, "LatexFile"));
		
		// By this one, you can directly input latex to a wiki article. LaTeX code is not interpreted in wiki-mode, though.
		$parser->setHook("rawtex",      array($this, "RawTex"));

		return true;
	}
	
	function w2lSetup(&$parser) {
		global $w2lTags;
		
		$w2lTags['rawtex'] = array($this, 'rawtex');
		
		// Some Extensions, which return LaTeX-commands
		$w2lTags['newpage'] = array($this, 'NewPage');
		$w2lTags['noindent'] = array($this, 'NoIndent');
		$w2lTags['label']    = array($this, 'Label');
		$w2lTags['pageref']  = array($this, 'PageRef');
		$w2lTags['chapref']  = array($this, 'ChapRef');
		
		// These Tags should not return a value in LaTeX-Mode.
		$w2lTags['templatevar'] = array($this, 'EmptyTag');
		$w2lTags['latexpage']   = array($this, 'EmptyTag');
		$w2lTags['latex']       = array($this, 'EmptyTag');
		
		return true;
	}

	function Latex($input, $argv, $parser, $frame, $mode = 'wiki') {
		$output = '<pre style="overflow:auto;">'.trim($input)."</pre>";

		return $output;
	}

	function LatexPage($input, $argv, $parser, $frame, $mode = 'wiki') {

		switch ($mode) {
			case 'wiki':
				$output = "<p><strong>Benoetigte Datei</strong>: ".$input."</p>";
				$output = "'''Ben&ouml;tigte Datei''': [[".$input."]]";
				$output = $parser->recursiveTagParse($output);

			break;
			case 'latex': $output = '';
			break;
			default: $output = $mode;
		}
		return $output;
	}

	function LatexFile($input, $argv, $parser, $frame, $mode = 'wiki') {
		$output  = '<strong>Dateiname:</strong> '.$argv['name'].'.tex';
		$output .= '<pre style="overflow:auto;">'.trim($input)."</pre>";

		return $output;
	}

	function TemplateVar($input, $argv, $parser, $frame, $mode = 'wiki') {
		$output = "<p><strong>".$argv['vname']."</strong>: ".$input."</p>";
		return $output;
	}

	// Latex-commands for Mediawiki
	function NoIndent($input, $argv, $parser, $frame, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				return '{\noindent}';
			default:
				return "";
		}
	}

	function NewPage($input, $argv, $parser, $frame, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				return '\clearpage{}';
			default:
				return '<hr/>';
		}
	}

	function Label($input, $argv, $parser, $frame, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				$output = '\label{'.$input.'}';
			break;
			default:
				$output = ' <strong>(Anker: '.$input.'<a id="'.$input.'"></a>)</strong>';
			break;
		}
		return $output;
	}

	function PageRef($input, $argv, $parser, $frame, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				$output = '\page{'.$input.'}';
			break;
			default:
				$output = '<a href="#'.$input.'" title="'.$input.'">S. XX</a>';
			break;
		}
		return $output;
	}

	function ChapRef($input, $argv, $parser, $frame, $mode = 'wiki') {
		switch($mode) {
			case 'latex':
				$output = '\ref{'.$input.'}';
			break;
			default:
				$output = '<a href="#'.$input.'" title="'.$input.'">X.Y</a>';
			break;
		}
		return $output;
	}

	function rawtex($input, $argv, $parser, $frame, $mode = 'wiki') {
		//global $w2l_config;

		switch ($mode) {
			case 'latex': 
				$output = $input;
			break;
			case 'wiki':
				$input  = trim($input);
	          		$output = '<pre style="overflow:auto;">'.$input."</pre>";
			break;
			default: $output = $input;
			break;
		}
		return $output;
	}

	function EmptyTag($input, $argv, $parser, $frame, $mode = 'wiki') {
		if ( $mode == 'latex' ) {
			return '';
		} else {
			return $input;
		}
	}
	
}
