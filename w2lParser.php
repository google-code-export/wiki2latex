<?php

/*
 * File: w2lParser.php
 *
 * Purpose:
 * Contains the parser, which transforms Mediawiki-articles to LaTeX
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

define('W2L_UNDEFINED', 'undefined');

define('W2L_FILE', 1);
define('W2L_STRING', 0);

define('W2L_TEMPLATE', 1);
define('W2L_TRANSCLUSION', 2);
define('W2L_COREPARSERFUNCTION', 3);
define('W2L_PARSERFUNCTION', 4);
define('W2L_VARIABLE', 5);


class Wiki2LaTeXParser {
	function __construct() {
		$this->version = W2L_VERSION;
		
		$this->initiated = false;
		$this->doProfiling = false;
		$this->ProfileLog  = array();
		$this->parsing     = '';
		$this->config      = array();
		$this->tags        = array();
		$this->fragments   = array();
		$this->elements    = array();
		$this->rawtex_counter = 0;
		$this->marks_counter = 0;
		$this->nowikiMarks = array();
		$this->nowikiCounter = 0;
		$this->rawtex_replace = array();
		// some default settings
		$this->config["headings_toplevel"] = 'section';
		$this->config["use_hyperref"]      = true;
		$this->config["leave_noinclude"]   = false;
		$this->config["babel"] = 'english';
		$this->tag_source  = array();
		$this->tag_replace = array();
		$this->tags_replace = array();
		$this->preReplace   = array();
		$this->replace_search  = array(); // NEVER set one of these values via another way than by addSimpleReplace
		$this->replace_replace = array();
		$this->ireplace_search  = array(); // NEVER set one of these values via another way than by addSimpleReplace
		$this->ireplace_replace = array();
		$this->regexp_search  = array(); // NEVER set one of these values via another way than by addRegExp
		$this->regexp_replace = array();
		$this->directives = array();
		$this->error_msg = array();
		$this->is_error = false;
		// takes parser functions

		$this->curlyBraceDebugCounter = 0;
		$this->curlyBraceLength = 0;


		$this->mw_vars = array();
		//%%$this->content_cache = array();
		// Parserfunctions...
		$this->pFunctions = array(); // takes custom ones (#switch)
		$this->cpFunctions = array(); // takes those without #
		$this->mask_chars = array();

		//%%$this->files_used = false;
		$this->files = array();
		$this->required_packages = array();
		$this->latex_headcode = array();
		
		// Mediawiki-Parser-Vars;
		$this->mLastSection = '';
		$this->mInPre = false;

		// Special-chars array...
		$this->sc = array();
		
		// For sorting and bibtex:
		$this->run_bibtex = false;
		$this->run_sort   = false;
		$this->debug = array();
	}

/* First things first: parse() and internalParse() are the most important functions */

	public function parse($text, &$title, $mode = W2L_STRING) {
		$this->profileIn(__METHOD__);
		/* parse a given wiki-string to latex */
		/* if $transclusions is an array, then all transcluded files are in there */
		$time_start = microtime(true);


		if ($this->initiated == false ) {
			$this->initParsing();
		}
		$this->mTitle =& $title;

		$text = trim($text);
		$text = "\n".$text."\n";

		wfRunHooks('w2lBeginParse', array( &$this, &$text ) );

		wfRunHooks('w2lBeforeCut', array( &$this, &$text ) );
		$text = $this->preprocessString($text);


		// First, strip out all comments...
		wfRunHooks('w2lBeforeStrip', array( &$this, &$text ) );
		$text = $this->stripComments($text);
		
		wfRunHooks('w2lBeforeExpansion', array( &$this, &$text ) );

		switch ( $this->getVal('process_curly_braces') ) {
			case '0': // remove everything between curly braces
				$text = preg_replace('/\{\{(.*?)\}\}/sm', '', $text);
			break;
			case '1': // do nothing
			break;
			case '2': // process them
				$text = $this->processCurlyBraces($text);
			break;
			default:
			break;
		}
		//$this->reportError($text, __METHOD__);
		$text = $this->getPerPageDirectives($text);
		wfRunHooks("w2lBeforeExtractTags", array( &$this, &$text ) );
		$text = $this->extractParserExtensions($text);
		$text = $this->extractPre($text);

		
		wfRunHooks("w2lBeforeInternalParse", array( &$this, &$text ) );
		
		$text = $this->internalParse($text);

		$text = trim($text);
		// Some tidying
		$text = str_replace("\n\n\n", "\n\n", $text);
		$text = trim(str_replace("\n\n\n", "\n\n", $text));
		// replace Extensions

		$text = $this->replacePre($text);
		$text = $this->replaceParserExtensions($text);
		$text = $this->replaceNoWikiMarkers($text);
		$text = $this->deMask($text);
		//$text = $this->replacePre($text);
		$text = trim($text);
		$text = str_replace("\n\n\n", "\n\n", $text);
		wfRunHooks("w2lFinish", array( &$this, &$text ) );

		$time_end = microtime(true);
		$this->parse_time = $time_end - $time_start;
		$this->profileOut(__METHOD__);
		return $text;
	}

	function internalParse($str) {
		$this->profileIn(__METHOD__);

		// Used for parsing the string as is, without comments, extension-tags, etc.

		//$str = $this->doSimpleReplace($str);
		
		$str = $this->doInternalLinks($str);
		$str = $this->doExternalLinks($str);
		
		wfRunHooks('w2lBeforeMask', array( &$this, &$str ) );
		$str = $this->maskLatexCommandChars($str);
		// Now we can begin parsing. We parse as close as possible the way mediawiki parses a string.
		// So, start with tables
		
		wfRunHooks('w2lBeforeTables', array( &$this, &$str ) );
		$str = $this->doTableStuff($str);

		// Next come these Blocklevel elments
		// Now go on with headings

		$str = $this->doHeadings($str);

		$str = $this->doQuotes($str);

		$str = $this->doHTML($str);
		$str = $this->doQuotationMarks($str);

		$str = $this->maskLatexSpecialChars($str);
		$str = $this->doSpecialChars($str);
		$str = $this->processHtmlEntities($str);
		$str = $this->maskLaTeX($str);
		$str = $this->doBlocklevels($str);
		$str = $this->maskMwSpecialChars($str);
		
		$str = $this->doDivAndSpan($str, 'span');
		$str = $this->doDivAndSpan($str, 'div');
		
		$str = $this->doSimpleReplace($str);


		wfRunHooks('w2lInternalFinish', array( &$this, &$str ) );

		$this->profileOut(__METHOD__);
		return $str;
    }

	public function recursiveTagParse( $str = '' ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$str = $this->internalParse($str);
		$this->profileOut($fName);
		return $str;
	}
	/* Public Functions */

	public function setConfig($cArray) {
		foreach ($cArray as $key=>$value) {
			$this->setVal($key, $value);
		}
		return true;
	}
	public function setVal($key, $value) {
		$this->config[$key] = $value;

		return true;
	}

	public function getVal($key) {
		if ( array_key_exists($key, $this->config) ) {
			return $this->config[$key];
		} else {
			return W2L_UNDEFINED;
		}
	}

	public function addSimpleReplace($search, $replace, $case_sensitive = 1) {
		if ($case_sensitive == 0 ) {
			$this->ireplace_search[]  = $search;
			$this->ireplace_replace[] = $replace;
		} else {
			$this->replace_search[]  = $search;
			$this->replace_replace[] = $replace;
		}
	}
	
	private function doSimpleReplace( $str ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		// Here we're replacing.
		$str = str_replace($this->replace_search, $this->replace_replace, $str);
		$str = str_ireplace($this->ireplace_search, $this->ireplace_replace, $str);
		$this->profileOut($fName);
		return $str;
	}

	public function addTagCallback($tag, $callback) {
		$this->tags[$tag] = $callback;
		$this->elements[] = $tag;
	}

	public function addParserFunction($tag, $callback) {
		$this->pFunctions[$tag] = $callback;
	}

	public function addCoreParserFunction($tag, $callback) {
		$this->addParserFunction($tag, $callback);
	}

	public function addRegExp($search, $replace) {
		$this->regexp_search[]  = $search;
		$this->regexp_replace[] = $replace;
	}

	function addChar($html, $latex, $utf_dec = false, $req_package = false) {
		if ($utf_dec === false ) {
			$ent_dec = '';
			$ent_hex = '';
		} else {
			$ent_dec = '&#'.$utf_dec.';';
			$ent_hex = '&#x'.dechex($utf_dec).';';
		}

		$this->htmlEntities[] = array(
			'html'    => $html,
			'utf_hex' => $ent_dec,
			'utf_dec' => $ent_hex,
			'latex'   => $latex,
			'xetex'   => '', // Future
			'required_package' => $req_package
		);
		return true;
	}

	function processHtmlEntities( $str ) {
		foreach($this->htmlEntities as $entity ) {
			$entity['html']    = str_replace('&', $this->Et, $entity['html']);
			$entity['utf_hex'] = str_replace('&', $this->Et, $entity['utf_hex']);
			$entity['utf_dec'] = str_replace('&', $this->Et, $entity['utf_dec']);
			
			if ($entity['required_package'] != false ) {
				if ( strpos($str, $entity['html']) !== false ) {
					$this->addPackageDependency($entity['required_package']);
				}
			}
			
			$str = strtr($str, array($entity['html'] => $entity['latex']));

			if ( $entity['utf_hex'] != '' ) {
				if ($entity['required_package'] != false ) {
					if ( strpos($str, $entity['utf_hex']) !== false ) {
						$this->addPackageDependency($entity['required_package']);
					}
				}
				
				$str = strtr($str, array($entity['utf_hex'] => $entity['latex']));
				$str = strtr($str, array($entity['utf_dec'] => $entity['latex']));
			}

			unset($entity);
		}

		return $str;
	}

	public function getPerformanceProfile($export_as = 'xml') {
		if ( !$this->doProfiling ) {
			return false;
		}
		switch ($export_as) {
			case 'array':
				return $this->ProfileLog;
			break;
			case 'xml':
				$xml_return = "";
				foreach($this->ProfileLog AS $func_call) {
					$xml_return .= '<'.$func_call['type'].' fname="'.$func_call['function'].'" time="'.$func_call['time'].'" />'."\n";
				}
				return $xml_return;
			break;
			default:
				return false;
			break;
		}
	}

	public function getParseTime() {
		return $this->parse_time;
	}

	private function doQuotationMarks($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);

		switch ( $this->getVal('babel') ) {
			case 'english':			
				$quotes = array(
					'"' => '"', // "
					"'" => "'", // '
				);
			break;
			case 'german': // using switch-fallthough here...
			case 'ngerman':
				$quotes = array(
					'"' => '\dq{}', // "
					"'" => '\rq{}', // '
				);
			break;
			default:
				$quotes = array(
					'"' => '"', // "
					"'" => "'", // '
				);
			break;		
		}

		$str = strtr($str, $quotes);

		$this->profileOut($fName);
		return $str;
	}
	/* Internal parsing functions */
	public function initParsing() {
		$fName = __METHOD__;
		$this->profileIn($fName);
		global $w2lTags;
		global $w2lParserFunctions;
		global $w2lConfig;

		if ($this->initiated == true ) {
			return;
		}
		
		wfRunHooks('w2lInitParser', array(&$this));
		
		$this->unique = $this->uniqueString();
		
		foreach($w2lTags as $key => $value) {
			$this->addTagCallback($key, $value);
		}

		foreach($w2lParserFunctions as $key => $value) {
			$this->addParserFunction($key, $value);
		}

		foreach($w2lConfig as $key => $value) {
			$this->setVal($key, $value);
		}

		//$this->addCoreParserFunction();
		$this->addCoreParserFunction( 'int', array( 'CoreParserFunctions', 'intFunction' ) );
		$this->addCoreParserFunction( 'ns', array( 'CoreParserFunctions', 'ns' )  );
		$this->addCoreParserFunction( 'urlencode', array( 'CoreParserFunctions', 'urlencode' )  );
		$this->addCoreParserFunction( 'lcfirst', array( 'CoreParserFunctions', 'lcfirst' )  );
		$this->addCoreParserFunction( 'ucfirst', array( 'CoreParserFunctions', 'ucfirst' )  );
		$this->addCoreParserFunction( 'lc', array( 'CoreParserFunctions', 'lc' )  );
		$this->addCoreParserFunction( 'uc', array( 'CoreParserFunctions', 'uc' )  );
		$this->addCoreParserFunction( 'localurl', array( 'CoreParserFunctions', 'localurl' )  );
		$this->addCoreParserFunction( 'localurle', array( 'CoreParserFunctions', 'localurle' )  );
		$this->addCoreParserFunction( 'fullurl', array( 'CoreParserFunctions', 'fullurl' )  );
		$this->addCoreParserFunction( 'fullurle', array( 'CoreParserFunctions', 'fullurle' )  );
		//$this->addCoreParserFunction( 'formatnum', array( 'CoreParserFunctions', 'formatnum' )  );
		//$this->addCoreParserFunction( 'grammar', array( 'CoreParserFunctions', 'grammar' )  );
		//$this->addCoreParserFunction( 'plural', array( 'CoreParserFunctions', 'plural' )  );
		$this->addCoreParserFunction( 'numberofpages', array( 'CoreParserFunctions', 'numberofpages' )  );
		$this->addCoreParserFunction( 'numberofusers', array( 'CoreParserFunctions', 'numberofusers' )  );
		$this->addCoreParserFunction( 'numberofarticles', array( 'CoreParserFunctions', 'numberofarticles' )  );
		$this->addCoreParserFunction( 'numberoffiles', array( 'CoreParserFunctions', 'numberoffiles' )  );
		$this->addCoreParserFunction( 'numberofadmins', array( 'CoreParserFunctions', 'numberofadmins' )  );
		$this->addCoreParserFunction( 'language', array( 'CoreParserFunctions', 'language' )  );
		$this->addCoreParserFunction( 'padleft', array( 'CoreParserFunctions', 'padleft' )  );
		$this->addCoreParserFunction( 'padright', array( 'CoreParserFunctions', 'padright' )  );
		$this->addCoreParserFunction( 'anchorencode', array( 'CoreParserFunctions', 'anchorencode' )  );
		$this->addCoreParserFunction( 'special', array( 'CoreParserFunctions', 'special' ) );
		//$this->addCoreParserFunction( 'defaultsort', array( 'CoreParserFunctions', 'defaultsort' )  );
		$this->addCoreParserFunction( 'pagesinnamespace', array( 'CoreParserFunctions', 'pagesinnamespace' ) );

		// And here we add some replace-rules
		$this->addSimpleReplace(" - "," -- ");
		$this->addSimpleReplace(" -\n"," --\n");
		$this->addSimpleReplace("\n- ", "\n-- ");

		$this->addSimpleReplace("...","{\dots}");

		
		include('w2lChars.php');
		include('w2lQuotes.php');
		
		wfRunHooks('w2lInitParserFinish', array(&$this));

		$this->initiated = true;
		$this->profileOut($fName);
		return;
	}
	
	function doSpecialChars($str) {

		$chars = array(
			"…"=>"{\dots}",
			"…"=>"{\dots}",
			'~'=> '\(\sim\)',
			'€'=> '{\euro}',
		);

		if ( strpos($str, '€') !== false ) {
			$this->addPackageDependency('eurosym');
		}

		$str = strtr($str, $chars);
		return $str;
	}

	function getFirstChar($str) {
		if ( strlen($str) == 0 ) {
			return '';
		} else {
			return $str{0};
		}
	}

	function extractPre($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$work_str = explode("\n", $str);
		$final_str = '';
		$debug = '';
		$pre_line = false;
		$block_counter = 0;

		$rplBlock = array();
		$preBlock = array();

		foreach($work_str as $line) {
			//wfVarDump($line);
			// every line is here, now check for a blank at first position
			// old code gives a notice on empty line:
			$first_char = $this->getFirstChar($line);


			$last_line = $pre_line;
			if ( ' ' == $first_char ) {
				if ($last_line == true) {

				} else {
					++$block_counter;
					$preBlock[$block_counter] = "";
				}

				$rpl_line = substr($line, 1);
				$preBlock[$block_counter] .= $rpl_line."\n";
				/*if ($this->getVal('debug') == true) {
					$preBlock[$block_counter] .= $rpl_line."\n"; // original-line
				} else {
					@$preBlock[$block_counter] .= $rpl_line."\n"; // The @ was added to suppress a notice...
				}*/
				$pre_line = true;
				$debug .= '1';
				$work_line = '';
			} else {
				$work_line = $line."\n";
				// check, wether last line was true, so we can create a block
				if ($last_line == true) {
					if ( trim($preBlock[$block_counter]) == "" ) {
						$work_line = $preBlock[$block_counter].$work_line;
					} else {
						$preBlockX = "\begin{verbatim}\n".$preBlock[$block_counter]."\end{verbatim}\n";
					
						//$work_line = $preBlock[$block_counter];
						//
						// originale Zeilen, latex-zeilen, marker,
						//
					
						//if ( $preBlock[$block_counter] ==  )
						$marker = $this->getMark('pre', $block_counter);
						$work_line = $marker.$work_line;
						//wfVarDump($str);
						//$str = str_replace($rplBlock[$block_counter], $marker, $str);
						//wfVarDump($str);
						$this->preReplace[$marker] = $preBlockX;
					}
				}

				$pre_line = false;
				$debug .= '0';
			}
			//$debug .= $pre_line;
			//wfVarDump($work_line);
			$final_str .= $work_line;
		}
		
		//wfVarDump($preBlock);
		$this->profileOut($fName);
		return $final_str;
	}

	function replacePre($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$str = str_replace(array_keys($this->preReplace), array_values($this->preReplace), $str);
		$this->profileOut($fName);
		return $str;
	}

	function matchNoWiki($str) {
		//
		$str = preg_replace_callback('/<nowiki>(.*)<\/nowiki>/smU', array($this,'noWikiMarker'), $str);
		return $str;
	}

	function noWikiMarker($match) {
		//
		++$this->nowikiCounter;
		$marker = $this->getMark('nowiki', $this->nowikiCounter);
		$str = $this->maskLatexCommandChars($match[1]);
		$str = $this->maskLatexSpecialChars($str);
		$str = $this->maskMwSpecialChars($str);
		$this->nowikiMarks[$marker] = $str;
		return $marker;
	}

	function replaceNoWikiMarkers($str) {
		//
		$str = strtr($str, $this->nowikiMarks);
		return $str;
	}

	public function preprocessString($str) {
		//$this->reportError(strlen($str), __METHOD__);
		$str = $this->matchNoWiki($str);
		$str = $this->stripComments($str);
		//$this->reportError(strlen($str), __METHOD__);
		if ( $this->getVal('leave_noinclude') ) {
			$str = preg_replace('/<noinclude>(.*)<\/noinclude>/smU', "$1", $str);
			$this->setVal('leave_noinclude', false);
		} else {
			$str = preg_replace('/<noinclude>.*<\/noinclude>/smU', '', $str);
		}

		if ( $this->getVal('insert_includeonly') ) {
			$str = preg_replace('/<includeonly>(.*)<\/includeonly>/smU', "$1", $str);
		} else {
			$str = preg_replace('/<includeonly>(.*)<\/includeonly>/smU', '', $str);
			$this->setVal('insert_includeonly', true);
		}

		//$this->reportError(strlen($str), __METHOD__);

		wfRunHooks('w2lPreProcess', array( &$this, &$str ) );
		//$this->reportError(strlen($str), __METHOD__);
		return $str;
	}

	private function doBlockLevels( $str = '' ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$fname=__METHOD__;
		$text = $str;
		$linestart = true;
		# Parsing through the text line by line.  The main thing
		# happening here is handling of block-level elements p, pre,
		# and making lists from lines starting with * # : etc.
		#
		$textLines = explode( "\n", $text );

		$lastPrefix = $output = '';
		$this->mDTopen = $inBlockElem = false;
		$prefixLength = 0;
		$paragraphStack = false;

		if ( !$linestart ) {
			$output .= array_shift( $textLines );
		}
		foreach ( $textLines as $oLine ) {
			$lastPrefixLength = strlen( $lastPrefix );
			$preCloseMatch = preg_match('/<\\/pre/i', $oLine );
			$preOpenMatch = preg_match('/<pre/i', $oLine );
			if ( !$this->mInPre ) {
				# Multiple prefixes may abut each other for nested lists.
				$prefixLength = strspn( $oLine, '*#:;' );
				$pref = substr( $oLine, 0, $prefixLength );

				# eh?
				$pref2 = str_replace( ';', ':', $pref );
				$t = substr( $oLine, $prefixLength );
				$this->mInPre = !empty($preOpenMatch);
			} else {
				# Don't interpret any other prefixes in preformatted text
				$prefixLength = 0;
				$pref = $pref2 = '';
				$t = $oLine;
			}

			# List generation
			if( $prefixLength && 0 == strcmp( $lastPrefix, $pref2 ) ) {
				# Same as the last item, so no need to deal with nesting or opening stuff
				$output .= $this->nextItem( substr( $pref, -1 ) );
				$paragraphStack = false;

				if ( substr( $pref, -1 ) == ';') {
					# The one nasty exception: definition lists work like this:
					# ; title : definition text
					# So we check for : in the remainder text to split up the
					# title and definition, without b0rking links.
					$term = $t2 = '';
					if ($this->findColonNoLinks($t, $term, $t2) !== false) {
						$t = $t2;
						$output .= $term . $this->nextItem( ':' );
					}
				}
			} elseif( $prefixLength || $lastPrefixLength ) {
				# Either open or close a level...
				$commonPrefixLength = $this->getCommon( $pref, $lastPrefix );
				$paragraphStack = false;

				while( $commonPrefixLength < $lastPrefixLength ) {
					$output .= $this->closeList( $lastPrefix{$lastPrefixLength-1} );
					--$lastPrefixLength;
				}
				if ( $prefixLength <= $commonPrefixLength && $commonPrefixLength > 0 ) {
					$output .= $this->nextItem( $pref{$commonPrefixLength-1} );
				}
				while ( $prefixLength > $commonPrefixLength ) {
					$char = substr( $pref, $commonPrefixLength, 1 );
					$output .= $this->openList( $char );

					if ( ';' == $char ) {
						# FIXME: This is dupe of code above
						if ($this->findColonNoLinks($t, $term, $t2) !== false) {
							$t = $t2;
							$output .= $term . $this->nextItem( ':' );
						}
					}
					++$commonPrefixLength;
				}
				$lastPrefix = $pref2;
			}
			if( 0 == $prefixLength ) {
				wfProfileIn( "$fname-paragraph" );
				# No prefix (not in list)--go to paragraph mode
				// XXX: use a stack for nestable elements like span, table and div

			}
			// somewhere above we forget to get out of pre block (bug 785)
			if($preCloseMatch && $this->mInPre) {
				$this->mInPre = false;
			}
			if ($paragraphStack === false) {
				$output .= $t."\n";
			}
		}
		while ( $prefixLength ) {
			$output .= $this->closeList( $pref2{$prefixLength-1} );
			--$prefixLength;
		}
		if ( '' != $this->mLastSection ) {
			$output .= '</' . $this->mLastSection . '>';
			$this->mLastSection = '';
		}
		$this->profileOut($fName);
		return $output;

	}

	/* private */ function nextItem( $char ) {
		if ( '*' == $char || '#' == $char ) { return '\item '; }
		else if ( ':' == $char || ';' == $char ) {
			$close = '\\\\';
			if ( $this->mDTopen ) { $close = ']'; }
			if ( ';' == $char ) {
				$this->mDTopen = true;
				return '\item[';
			} else {
				$this->mDTopen = false;
				return $close . '';
			}
		}
		return '<!-- ERR 2 -->';
	}
	/* private */ function closeParagraph() {
		$result = '';
		if ( '' != $this->mLastSection ) {
			$result = '</' . $this->mLastSection  . ">\n";
		}
		$this->mInPre = false;
		$this->mLastSection = '';
		return $result;
	}
	/* private */ function openList( $char ) {
		$list_ul_env = 'itemize';
		$list_ol_env = 'enumerate';	 
		wfRunHooks('w2lParseLists', array(&$this, &$list_ul_env, &$list_ol_env) );
	   
		$result = $this->closeParagraph();

		if ( '*' == $char ) { $result .= '\begin{'.$list_ul_env.'}'."\n".'\item '; }
		else if ( '#' == $char ) { $result .= '\begin{'.$list_ol_env.'}'."\n".'\item '; }
		else if ( ':' == $char ) { $result .= "\begin{description}\n\item[]"; }
		else if ( ';' == $char ) {
			$result .= "\begin{description}\n\item[";
			$this->mDTopen = true;
		}
		else { $result = '<!-- ERR 1 -->'; }

		return $result;
	}
	/* private */ function closeList( $char ) {
		$list_ul_env = 'itemize';
		$list_ol_env = 'enumerate';	 
		wfRunHooks('w2lParseLists', array(&$this, &$list_ul_env, &$list_ol_env) );

		if ( '*' == $char ) { $text = '\end{'.$list_ul_env.'}'; }
		else if ( '#' == $char ) { $text = '\end{'.$list_ol_env.'}'; }
		else if ( ':' == $char ) {
			if ( $this->mDTopen ) {
				$this->mDTopen = false;
				$text = "]\n\end{description}";
			} else {
				$text = "\n\end{description}";
			}
		}
		else {	return '<!-- ERR 3 -->'; }
		return $text."\n";
	}
	/* private */ function getCommon( $st1, $st2 ) {
		$fl = strlen( $st1 );
		$shorter = strlen( $st2 );
		if ( $fl < $shorter ) { $shorter = $fl; }

		for ( $i = 0; $i < $shorter; ++$i ) {
			if ( $st1{$i} != $st2{$i} ) { break; }
		}
		return $i;
	}

	/**
	 * Split up a string on ':', ignoring any occurences inside tags
	 * to prevent illegal overlapping.
	 * @param string $str the string to split
	 * @param string &$before set to everything before the ':'
	 * @param string &$after set to everything after the ':'
	 * return string the position of the ':', or false if none found
	 */
	function findColonNoLinks($str, &$before, &$after) {
		$fname = 'Parser::findColonNoLinks';
		//wfProfileIn( $fname );

		$pos = strpos( $str, ':' );
		if( $pos === false ) {
			// Nothing to find!
			wfProfileOut( $fname );
			return false;
		}

		$lt = strpos( $str, '<' );
		if( $lt === false || $lt > $pos ) {
			// Easy; no tag nesting to worry about
			$before = substr( $str, 0, $pos );
			$after = substr( $str, $pos+1 );
			//wfProfileOut( $fname );
			return $pos;
		}
	}

	private function doHeadings( $str = '' ) {
		$this->profileIn(__METHOD__);
		// Here we're going to parse headings
		// Without support for \part. Needs to be implemented seperately...
		// Method from mediawiki
		for ( $i = 6; $i >= 1; --$i ) {
			$h = str_repeat( '=', $i );
			$str = preg_replace( "/^{$h}(.+){$h}\\s*$/m", "<h{$i}>\\1</h{$i}>\\2", $str );
		}
		
		if ( preg_match("/^<h1>(.+)<\/h1>/", $str)) {
			$str = preg_replace("/^<h5>(.+)<\/h5>\\s*$/m","<h6>\\1</h6>\\2", $str);
			$str = preg_replace("/^<h4>(.+)<\/h4>\\s*$/m","<h5>\\1</h5>\\2", $str);
			$str = preg_replace("/^<h3>(.+)<\/h3>\\s*$/m","<h4>\\1</h4>\\2", $str);
			$str = preg_replace("/^<h2>(.+)<\/h2>\\s*$/m","<h3>\\1</h3>\\2", $str);
			$str = preg_replace("/^<h1>(.+)<\/h1>\\s*$/m","<h2>\\1</h2>\\2", $str);
		}

		//$pr_match = ;
		$str = preg_replace_callback('^\<h([1-6])\>(.+)\</h([1-6])\>^', array($this, 'processHeadings'), $str);
		//$str = str_ireplace($headings_html, $headings_latex, $str);

		$this->profileOut(__METHOD__);
		return $str;
	}

	private function processHeadings($matches) {
		$heading = trim($matches[2]);
		$level = trim($matches[1]);

		if ( in_array( $this->getVal("documentclass"), array('report' ,'book'))) {
			--$level;
		}

		// Beware: using chapter removes support for \subparagraph
		$headings_latex = $headings_latex = array('part', 'chapter', 'section',  'subsection',  'subsubsection', 'paragraph', 'subparagraph');
		
		$asteriks = $this->getMark('Asteriks');
		$this->sc['asteriks'] = $asteriks;
		$this->mask($asteriks, '*');

		$heading_command = '';

		wfRunHooks( 'w2lHeadings', array(&$this, &$heading, &$level, &$heading_command) );

		if ( substr($heading, 0, 1) == '*' ) {
			// *
			$heading = trim(substr($heading, 1));
			return '\\'.$headings_latex[$level].$asteriks.'{'.$heading.'}';
		} else {
			// standard
			if ( $heading_command == '' ) {
				// it hasn't been changed, so 
				$heading_command = $headings_latex[$level];
			}       
			return '\\'.$heading_command.'{'.trim($heading).'}';
		}

	}



	private function doInternalLinks( $str = '' ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		// match everything within [[...]]
		$str = preg_replace_callback('/\[\[(.*?)\]\]/', array($this, 'internalLinkHelper'), $str);
		$this->profileOut($fName);
		return $str;
	}

	private function internalLinkHelper($matches) {
		// Here we can handle every possibility of links:
		// category-links, image-links, Page-links... Whatever
		$matches[1] = trim($matches[1]);
		
		$link_tmp = explode("|", $matches[1], 2);

		$link_int  = $link_tmp[0];
		$link_text = ( isset($link_tmp[1]) ) ? $link_tmp[1] : $link_tmp[0];
		//%%
		unset($link_tmp);
		
		$title = Title::newFromText($link_int);
		
		if ( !is_a($title, 'Title') ) {
			// no title object, so error out!
			return $matches[1];
		}
		
		if ( true == $title->isExternal() ) {
			// Interwiki-Link

			$link_url = $title->escapeFullURL(); // This one contains some HTML code which is bad
			// Get the first occurrence of ", and remove everything else

			$remove_from = strpos('"', $link_url );
			if ( $remove_from > 1) {
				$link_url    = substr(0, $remove_from, $link_url);
			}
			$command     = $link_text;

			wfRunHooks('w2lInterwikiLinks', array(&$this, &$link_url, &$link_text, &$command) );
			
			return $command;
		}
		
		switch ( $title->getNamespace() ) {
			case NS_MEDIA:
				// this is just a link to the mediawiki-page
				return $link_text;
			break;
			case NS_IMAGE:
				$link = $title;

				$parts = explode("|", $matches[1]);
				$imagename = array_shift($parts);
				$case_imagename = $imagename;
				// still need to remove the Namespace:
				$tmp_name = explode(':', $imagename, 2);
				$imagename = $tmp_name[1];

				$imgwidth = "10cm";
				foreach ($parts as $part) {
					if (preg_match("/\d+px/", $part)) continue;

					if (preg_match("/(\d+cm)/", $part, $widthmatch)) {
						$imgwidth = $widthmatch[1];
						continue;
					}

					if (preg_match("/thumb|thumbnail|frame/", $part)) continue;
					if (preg_match("/left|right|center|none/", $part)) continue;

					$caption = trim($part);
				}
				$title = Title::makeTitleSafe( NS_IMAGE, $imagename );
				$this->repo = RepoGroup::singleton()->getLocalRepo();
				$file = LocalFile::newFromTitle( $title, $this->repo );
				$file->loadFromFile();
				if ( $file && $file->exists() ) {
					$imagepath = $file->getPath();
					$imagepath = str_replace('\\', '/', $imagepath);
				} else {
					// does not exist!!!
					$case_imagename = str_replace('_', ' ', $case_imagename);
					return $case_imagename;
				}

				//%%$title = $file->getTitle()->getText();
				$graphic_package = 'graphicx';
				$graphic_command = "\\begin{center} \\resizebox{".$imgwidth."}{!}{\includegraphics{{$imagepath}}}\\\\ \\textit{{$caption}}\end{center}\n";
				wfRunHooks('w2lImage', array(&$this, &$file, &$graphic_package, &$graphic_command, &$imagepath, &$imagename, &$imgwith, &$caption));
			
				$this->addPackageDependency($graphic_package);
				$masked_command = $this->getMark($graphic_package);
				$this->mask($masked_command, $graphic_command);
				return $masked_command;

			break;
			case NS_CATEGORY:
				// Namespace is a category, but a plain link to a cat-page is also matched here...
				if ( $link_int{0} != ':' ) {
					wfRunHooks('w2lAddCategory', array(&$this, &$link_int) );
					return '';
				} // else: Fall through
			default:
				$link_url  = $title->getFullUrl();
				$link_page = $title->getDBKey();
				$command   = $link_text;
				wfRunHooks('w2lInternalLinks', array(&$this, &$command, &$link_text, &$link_page, &$link_url) );
				return $command;
			break;
		}

		return $link_text;

	}

	private function doExternalLinks( $str ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		// Match everything within [...]
		$str = preg_replace_callback('/\[(.*?)\]/', array($this, 'externalLinkHelper'), $str);
		
		// Now check for plain external links:
		$str = $this->replaceFreeExternalLinks($str);
		
		$this->profileOut($fName);
		return $str;
	}
	
	/**
	 * Replace anything that looks like a URL with a link
	 * @private
	 */
	function replaceFreeExternalLinks( $text ) {
		//global $wgContLang;
		//$fname = 'Parser::replaceFreeExternalLinks';
		//wfProfileIn( $fname );

		$bits = preg_split( '/(\b(?:' . wfUrlProtocols() . '))/S', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$s = array_shift( $bits );
		$i = 0;
		//$sk = $this->mOptions->getSkin();

		while ( $i < count( $bits ) ){
			$protocol = $bits[$i++];
			$remainder = $bits[$i++];
			
			$m = array();

			if ( preg_match( '/^([^][<>"\\x00-\\x20\\x7F]+)(.*)$/s', $remainder, $m ) ) {
				# Found some characters after the protocol that look promising
				$url = $protocol . $m[1];
				$trail = $m[2];
				
				# special case: handle urls as url args:
				# http://www.example.com/foo?=http://www.example.com/bar
				if(strlen($trail) == 0 &&
					isset($bits[$i]) &&
					preg_match('/^'. wfUrlProtocols() . '$/S', $bits[$i]) &&
					preg_match( '/^([^][<>"\\x00-\\x20\\x7F]+)(.*)$/s', $bits[$i + 1], $m ))
				{
					# add protocol, arg
					$url .= $bits[$i] . $m[1]; # protocol, url as arg to previous link
					$i += 2;
					$trail = $m[2];
				}

				# The characters '<' and '>' (which were escaped by
				# removeHTMLtags()) should not be included in
				# URLs, per RFC 2396.
				$m2 = array();
				if (preg_match('/&(lt|gt);/', $url, $m2, PREG_OFFSET_CAPTURE)) {
					$trail = substr($url, $m2[0][1]) . $trail;
					$url = substr($url, 0, $m2[0][1]);
				}

				# Move trailing punctuation to $trail
				$sep = ',;\.:!?';
				# If there is no left bracket, then consider right brackets fair game too
				if ( strpos( $url, '(' ) === false ) {
					$sep .= ')';
				}

				$numSepChars = strspn( strrev( $url ), $sep );
				if ( $numSepChars ) {
					$trail = substr( $url, -$numSepChars ) . $trail;
					$url = substr( $url, 0, -$numSepChars );
				}

				$url = Sanitizer::cleanUrl( $url );

				# Is this an external image?
				$text = false;// $this->maybeMakeExternalImage( $url );
				if ( $text === false ) {

					$text = $this->externalLinkHelper(array("[$url]", $url));
					# Not an image, make a link
					//$text = $sk->makeExternalLink( $url, $wgContLang->markNoConversion($url), true, 'free', $this->mTitle->getNamespace() );
					# Register it in the output object...
					# Replace unnecessary URL escape codes with their equivalent characters
					//$pasteurized = Parser::replaceUnusualEscapes( $url );
					//$this->mOutput->addExternalLink( $pasteurized );
				}
				$s .= $text . $trail;
			} else {
				$s .= $protocol . $remainder;
			}
		}
		//wfProfileOut( $fname );
		return $s;
	}
	
	private function externalLinkHelper($matches) {
		$match = trim($matches[1]);
		// check link for ...
		$pattern = '/(http|https|ftp|ftps):(.*?)/';
		if ( !preg_match($pattern, $match) ) {
			return "[".$match."]";
		}
		
		$hr_options = 'pdfborder={0 0 0}, breaklinks=true, pdftex=true, raiselinks=true';
		wfRunHooks('w2lNeedHyperref', array(&$this, &$hr_options) );
		
		$this->addPackageDependency('hyperref', $hr_options);
		//$this->addPackageDependency('breakurl');
		
		if ( strstr($match, ' ') !== false ) {
			// mit Text!
			$link = explode(' ', $match, 2); // in $link[0] ist die URL!
			$linkCom = $this->maskURL($link[0], $link[1]);
			// (Befehl)(Klammerauf)(Link_masked)(Klammerzu)(Klammerauf)LinkText(Klammerzu)
		} else {
			// nur URL!
			$linkCom = $this->maskURL($match);
		}

		return $linkCom;
	}

	function maskURL($url, $text = '') {
		// (Befehl)(Klammerauf)(Link_masked)(Klammerzu)(Klammerauf)LinkText(Klammerzu)
		$mask_open  = $this->getMark('CurlyOpen');
		$mask_close = $this->getMark('CurlyClose');
		$mask_url = $this->getMark('EXTERNAL-URL');
		$mask_com = $this->getMark('LinkCommand');
		if ( '' == $text ) {
			$link = $mask_com.$mask_open.$mask_url.$mask_close;
			$this->mask($mask_open,  '{');
			$this->mask($mask_close, '}');
			$this->mask($mask_url,   $url);
			$this->mask($mask_com,   '\url');
		} else {
			$link = $mask_com.$mask_open.$mask_url.$mask_close.$mask_open.$text.$mask_close;
			$this->mask($mask_open,  '{');
			$this->mask($mask_close, '}');
			$this->mask($mask_url,   $url);
			$this->mask($mask_com,   '\href');
		}
		return $link;
	}
	private function extractParserExtensions( $str = '' ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$matches = array();
		$unique  = 'W2l-'.$this->uniqueString();
		//$unique .=

		$str = $this->extractTagsAndParams($this->elements, $str, $matches, $unique);

		// second: Some other aspects...
		// Now call all the registered Callback-function with their contents.
		foreach($matches as $key => $match) {
			//%%$input = $match[1];
			//%%$tag = $match[0];
			//%%$argv = array();
			//%%$argv = $match[2];
			// Submitting false as $frame for now :(
			$rpl = call_user_func($this->tags[$match[0]], $match[1], $match[2], $this, false, 'latex');
			$this->tag_replace["$key"] = $rpl;
		}
		$this->profileOut($fName);

		return $str;
	}

	private function replaceParserExtensions( $str ) {
		$fName = __METHOD__;
		$this->profileIn($fName);

		$str = str_replace(array_keys($this->tag_replace), array_values($this->tag_replace), $str);
		$this->profileOut($fName);
		return $str;
	}

	private function extractTagsAndParams($elements, $text, &$matches, $uniq_prefix = ''){
		static $n = 1;
		$stripped = '';
		$matches = array();

		$taglist = implode( '|', $elements );
		$start = "/<($taglist)(\\s+[^>]*?|\\s*?)(\/?>)|<(!--)/i";

		while ( '' != $text ) {
			$p = preg_split( $start, $text, 2, PREG_SPLIT_DELIM_CAPTURE );
			$stripped .= $p[0];
			if( count( $p ) < 5 ) {
				break;
			}
			if( count( $p ) > 5 ) {
				// comment
				$element    = $p[4];
				$attributes = '';
				$close      = '';
				$inside     = $p[5];
			} else {
				// tag
				$element    = $p[1];
				$attributes = $p[2];
				$close      = $p[3];
				$inside     = $p[4];
			}

			//$marker = "($uniq_prefix-$element-" . sprintf('%08X', $n++) . '-QINU)';
			$marker = $this->getMark($element, $n++);
			$stripped .= $marker;

			if ( $close === '/>' ) {
				// Empty element tag, <tag />
				$content = null;
				$text = $inside;
				$tail = null;
			} else {
				if( $element == '!--' ) {
					$end = '/(-->)/';
				} else {
					$end = "/(<\\/$element\\s*>)/i";
				}
				$q = preg_split( $end, $inside, 2, PREG_SPLIT_DELIM_CAPTURE );
				$content = $q[0];
				if( count( $q ) < 3 ) {
					# No end tag -- let it run out to the end of the text.
					$tail = '';
					$text = '';
				} else {
					$tail = $q[1];
					$text = $q[2];
				}
			}

			$matches[$marker] = array( $element,
				$content,
				Sanitizer::decodeTagAttributes( $attributes ),
				"<$element$attributes$close$content$tail" );
		}
		return $stripped;
	}

	private function doQuotes( $text ) {
		$fName = __METHOD__;
		$this->profileIn($fName);

		$arr = preg_split( "/(''+)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( count( $arr ) == 1 ) {
			// No char. return;
			$this->profileOut($fName);

			return $text;
    	} else {
			# First, do some preliminary work. This may shift some apostrophes from
			# being mark-up to being text. It also counts the number of occurrences
			# of bold and italics mark-ups.
			$i = 0;
			$numbold = 0;
			$numitalics = 0;
			foreach ( $arr as $r )
			{
				if ( ( $i % 2 ) == 1 )
				{
					# If there are ever four apostrophes, assume the first is supposed to
					# be text, and the remaining three constitute mark-up for bold text.
					if ( strlen( $arr[$i] ) == 4 )
					{
						$arr[$i-1] .= "'";
						$arr[$i] = "'''";
					}
					# If there are more than 5 apostrophes in a row, assume they're all
					# text except for the last 5.
					else if ( strlen( $arr[$i] ) > 5 )
					{
						$arr[$i-1] .= str_repeat( "'", strlen( $arr[$i] ) - 5 );
						$arr[$i] = "'''''";
					}
					# Count the number of occurrences of bold and italics mark-ups.
					# We are not counting sequences of five apostrophes.
					if ( strlen( $arr[$i] ) == 2 ) $numitalics++;  else
					if ( strlen( $arr[$i] ) == 3 ) $numbold++;     else
					if ( strlen( $arr[$i] ) == 5 ) { $numitalics++; $numbold++; }
				}
				$i++;
			}

			# If there is an odd number of both bold and italics, it is likely
			# that one of the bold ones was meant to be an apostrophe followed
			# by italics. Which one we cannot know for certain, but it is more
			# likely to be one that has a single-letter word before it.
			if ( ( $numbold % 2 == 1 ) && ( $numitalics % 2 == 1 ) )
			{
				$i = 0;
				$firstsingleletterword = -1;
				$firstmultiletterword = -1;
				$firstspace = -1;
				foreach ( $arr as $r )
				{
					if ( ( $i % 2 == 1 ) and ( strlen( $r ) == 3 ) )
					{
						$x1 = substr ($arr[$i-1], -1);
						$x2 = substr ($arr[$i-1], -2, 1);
						if ($x1 == ' ') {
							if ($firstspace == -1) $firstspace = $i;
						} else if ($x2 == ' ') {
							if ($firstsingleletterword == -1) $firstsingleletterword = $i;
						} else {
							if ($firstmultiletterword == -1) $firstmultiletterword = $i;
						}
					}
					$i++;
				}

				# If there is a single-letter word, use it!
				if ($firstsingleletterword > -1)
				{
					$arr [ $firstsingleletterword ] = "''";
					$arr [ $firstsingleletterword-1 ] .= "'";
				}
				# If not, but there's a multi-letter word, use that one.
				else if ($firstmultiletterword > -1)
				{
					$arr [ $firstmultiletterword ] = "''";
					$arr [ $firstmultiletterword-1 ] .= "'";
				}
				# ... otherwise use the first one that has neither.
				# (notice that it is possible for all three to be -1 if, for example,
				# there is only one pentuple-apostrophe in the line)
				else if ($firstspace > -1)
				{
					$arr [ $firstspace ] = "''";
					$arr [ $firstspace-1 ] .= "'";
				}
			}

			# Now let's actually convert our apostrophic mush to HTML!
			$output = '';
			$buffer = '';
			$state = '';
			$i = 0;
			foreach ($arr as $r)
			{
				if (($i % 2) == 0)
				{
					if ($state == 'both')
						$buffer .= $r;
					else
						$output .= $r;
				}
				else
				{
					if (strlen ($r) == 2)
					{
						if ($state == 'i')
						{ $output .= '</i>'; $state = ''; }
						else if ($state == 'bi')
						{ $output .= '</i>'; $state = 'b'; }
						else if ($state == 'ib')
						{ $output .= '</b></i><b>'; $state = 'b'; }
						else if ($state == 'both')
						{ $output .= '<b><i>'.$buffer.'</i>'; $state = 'b'; }
						else # $state can be 'b' or ''
						{ $output .= '<i>'; $state .= 'i'; }
					}
					else if (strlen ($r) == 3)
					{
						if ($state == 'b')
						{ $output .= '</b>'; $state = ''; }
						else if ($state == 'bi')
						{ $output .= '</i></b><i>'; $state = 'i'; }
						else if ($state == 'ib')
						{ $output .= '</b>'; $state = 'i'; }
						else if ($state == 'both')
						{ $output .= '<i><b>'.$buffer.'</b>'; $state = 'i'; }
						else # $state can be 'i' or ''
						{ $output .= '<b>'; $state .= 'b'; }
					}
					else if (strlen ($r) == 5)
					{
						if ($state == 'b')
						{ $output .= '</b><i>'; $state = 'i'; }
						else if ($state == 'i')
						{ $output .= '</i><b>'; $state = 'b'; }
						else if ($state == 'bi')
						{ $output .= '</i></b>'; $state = ''; }
						else if ($state == 'ib')
						{ $output .= '</b></i>'; $state = ''; }
						else if ($state == 'both')
						{ $output .= '<i><b>'.$buffer.'</b></i>'; $state = ''; }
						else # ($state == '')
						{ $buffer = ''; $state = 'both'; }
					}
				}
				$i++;
			}
			# Now close all remaining tags. Notice that the order is important.
			if ($state == 'b' || $state == 'ib')
				$output .= '</b>';
			if ($state == 'i' || $state == 'bi' || $state == 'ib')
				$output .= '</i>';
			if ($state == 'bi')
				$output .= '</b>';
			if ($state == 'both')
				$output .= '<b><i>'.$buffer.'</i></b>';
		}
		$this->profileOut($fName);
		return $output;
	}

	private function doRegExp( $str ) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		// Here we're going to run all these regexps
		$str = preg_replace($this->regexp_search, $this->regexp_replace, $str);
		$this->profileOut($fName);
		return $str;
	}

	private function doTableStuff( $str ) {
		$this->profileIn(__METHOD__);
		
		//if ( preg_match('/\\{\|/', $str) ) {
			$correct = array("\n\{|" => "\n{|", "|\}\n"=> "|}\n");
			$str = str_replace(array_keys($correct), array_values($correct), $str);
		
			wfRunHooks("w2lTables", array( &$this, &$str ) );

			$str = $this->externalTableHelper($str);
		//}

		$this->profileOut(__METHOD__);
		return $str;
	}

        /*
       	 * Restores pre, math, and other extensions removed by strip()
	 *
	 * always call unstripNoWiki() after this one
	 * @private
	 */
	private function unstrip( $text, &$state ) {
		if ( !isset( $state['general'] ) ) {
			return $text;
		}

		wfProfileIn( __METHOD__ );
		# TODO: good candidate for FSS
		$text = strtr( $text, $state['general'] );
		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * Always call this after unstrip() to preserve the order
	 *
	 * @private
	 */
	private function unstripNoWiki( $text, &$state ) {
		if ( !isset( $state['nowiki'] ) ) {
			return $text;
		}

		wfProfileIn( __METHOD__ );
		# TODO: good candidate for FSS
		$text = strtr( $text, $state['nowiki'] );
		wfProfileOut( __METHOD__ );

		return $text;
	}

	private function unstripForHTML( $text ) {
		$text = $this->unstrip( $text, $this->mStripState );
		$text = $this->unstripNoWiki( $text, $this->mStripState );
		$this->addLatexHeadCode('\\newcolumntype{Y}{>{\\raggedright}X}');
		return $text;
	}

	/*
	 * externalTableHelper is really a transplanted version of Parser::doTableStuff
	 * from mediaWiki. Translates wiki tables to LaTeX tables.
	 * Currently ingnores all attributes of the table, except latexfmt, which tells
	 * how many rows there are, and which type of cells should be used for each row.
	 * An extra cell type Y is introduced for left-aligned text than can wrap.
	 * Example: " {| latexfmt="|l|X|Y|l| ..."
	 */
	
	private function externalTableHelper($t) {
		$latexformat = '';
		$t = trim($t);
		$t = explode ( "\n" , $t ) ;
		$ltd = array () ; # Is current cell TD or TH?
		$tr = array () ; # Is currently a tr tag open?
		$ltr = array () ; # tr attributes
		$cellcount_max = array();
		$cellcount_current = array();
		$tableheader = array();
		$thkr = array(); # table header index array
		$th = 0;
		$has_opened_tr = array(); # Did this table open a <tr> element?
		$anyCells = false;
		$firstCellOfRow = true;
		$ltx_caption = '';
		$in_table = 0;

		foreach ( $t AS $k => $x )
		{
			$x = trim ( $x ) ;
			if ( $x == '' ) { // empty line, go to next line
				continue;
			}
			$fc = substr ( $x , 0 , 1 ) ;
			//$matches = array();
			if ( preg_match( '/^(:*)\{\|(.*)$/', $x, $matches ) ) {
                

				
				/*
				preg_match("/latexfmt=\"(.*?)\"/", $attributes, $latexformat);
				$latexwidth = '\linewidth';
				if ( preg_match("/latexwidth=\"(.*?)\"/", $attributes, $latexwidth_a) ) {
					$latexwidth = $latexwidth_a[1];
					$latexwidth = str_replace('\(\backslash{}\)', '\\', $latexwidth);
				}

				
				$latexformat = $latexformat[1];
				$latexformat = str_replace("\\", "", $latexformat);*/

				if ( $in_table == 0 ) { /* new top-level table, initialise arrays */
					$latexformat = '';
					$cellcount_max = array();
					$cellcount_current = array();
					$tableheader = array();
					$thkr = array(); # table header index array
					$th = 0;
				}

				$in_table++;
				array_push ( $ltd , '' ) ;
				array_push ( $tr , false ) ;
				array_push ( $ltr , '' ) ;
				array_push ( $has_opened_tr, false );

				//Start of table: Extract LaTeX tips from attributes, make header.
				$attributes = $this->unstripForHTML( $matches[2] );
                $this->debugMessage('Table: Attributes: ', $attributes);
                $attributes = str_replace($this->sc['backslash'], '\\', $attributes);
				$attributes_test = $this->parseAttrString($attributes);
				
				if ( array_key_exists('latexfmt', $attributes_test) ) {
					$latexformat = $attributes_test['latexfmt'];
					$latexformat = str_replace("\\", "", $latexformat);
                    $this->debugMessage('Table: latexfmt: ', $latexformat);
				}
				
				if ( array_key_exists('latexwidth', $attributes_test) ) {
					$latexwidth = $attributes_test['latexwidth'];
					$latexwidth = str_replace('\(\backslash{}\)', '\\', $latexwidth);
                    $this->debugMessage('Table: latexwidth: ', $latexwidth);
				} else {
					$latexwidth = '\linewidth';
				}

				// start-of-table
				array_push ( $thkr, $k );
				$tableheader[$in_table]['width'] = $latexwidth;
				$tableheader[$in_table]['format'] = $latexformat;
				$cellcount_max[$in_table] = 0;
				// start-of-row
				$cellcount_current[$in_table] = 0;

				$this->addPackageDependency('tabularx');
				$firstCellOfRow=true;
			}
			else if ( ('|}' == substr ( $x , 0 , 2 )) || ('|\}' == substr ( $x , 0 , 3 ))) {
				//End of table. Pop stacks and print latex ending.
				$l = array_pop ( $ltd ) ;
				if ( !array_pop ( $has_opened_tr ) ) $t[$k-1] = $t[$k-1] . "\\tabularnewline \hline";
				if ( array_pop ( $tr ) ) $t[$k-1] = $t[$k-1] . '\\tabularnewline \hline';
				array_pop ( $ltr ) ;

				// end-of-row code
				$cellcount_max[$in_table] = max( $cellcount_max[$in_table] , $cellcount_current[$in_table] );
				// end-of-table
				$thk = array_pop ( $thkr );
				$latexwidth = $tableheader[$in_table]['width'];
				if ( $tableheader[$in_table]['format'] == '' ) {
					$latexformat = array();
					for ( $i = 0; $i < $cellcount_max[$in_table]; $i++ ) { array_push ( $latexformat , 'Y' ); }
					$latexformat = '|'.implode( '|' , $latexformat ).'|';
				} else {
					$latexformat = $tableheader[$in_table]['format'];
				}
				if ($in_table > 1) {
					$t[$thk] = "{\begin{tabularx}{{$latexwidth}}{{$latexformat}}\\hline";
					$t[$k] = "\\end{tabularx}}".trim($ltx_caption);
				} else {
                    // This table is not nested
                    $this->debugMessage('Table: inserted latexfmt: ', $latexformat);
                    $this->debugMessage('Table: inserted latexwidth ', $latexwidth);
                    wfRunHooks("w2lTableLaTeXAttributes", array(&$this, &$latexformat, &$latexwidth));
                    $table_head = "\begin{tabularx}{{$latexwidth}}{{$latexformat}}\\hline";
                    $table_foot = "\\end{tabularx}\n".trim($ltx_caption);
                    wfRunHooks("w2lTableHead", array(&$this, &$table_head));
                    wfRunHooks("w2lTableFoot", array(&$this, &$table_foot));
					$t[$thk] = $table_head;
					$t[$k] = $table_foot;
                    unset($table_head, $table_foot);
				}

				$in_table--;
				$ltx_caption = '';
			}
			else if ( '|-' == substr ( $x , 0 , 2 ) ) { # Allows for |---------------
				if (strpos($x, '----') == 1) {
					$add_hline = '\hline';

				} else {
					$add_hline = '';
				}
				$x = substr ( $x , 1 ) ;
				while ( $x != '' && substr ( $x , 0 , 1 ) == '-' ) $x = substr ( $x , 1 ) ;
				$z = '' ;
				$l = array_pop ( $ltd ) ;
				array_pop ( $has_opened_tr );
				array_push ( $has_opened_tr , true ) ;

				if ( array_pop ( $tr ) ) $t[$k-1] = $t[$k-1] . '\\tabularnewline \hline'.$add_hline;
				array_pop ( $ltr ) ;
				$t[$k] = $z ;
				array_push ( $tr , false ) ;
				array_push ( $ltd , '' ) ;

				// end-of-row
				$cellcount_max[$in_table] = max( $cellcount_max[$in_table] , $cellcount_current[$in_table] );
				// start-of-row
				$cellcount_current[$in_table] = 0;

				$attributes = $this->unstripForHTML( $x );
				array_push ( $ltr , Sanitizer::fixTagAttributes ( $attributes, 'tr' ) ) ;
				$firstCellOfRow = true;
				$add_hline = '';
				//$cellcounter[] = 0;
			}
			else if ( ('|' === $fc || '!' === $fc || '|+' === substr ( $x , 0 , 2 ) ) && $in_table != 0 ) { # Caption

				# $x is a table row
				if ( '|+' == substr ( $x , 0 , 2 ) ) {
					$fc = '+' ;
					$x = substr ( $x , 1 ) ;
				}
				$after = substr ( $x , 1 ) ;
				if ( $fc == '!' ) $after = str_replace ( '!!' , '||' , $after ) ;
				
				// Split up multiple cells on the same line.
				// FIXME: This can result in improper nesting of tags processed
				// by earlier parser steps, but should avoid splitting up eg
				// attribute values containing literal "||".

				$cells = StringUtils::explodeMarkup( '||', $after );

				$t[$k] = '' ;
				# Loop through each table cell
				foreach ( $cells AS $theline )
				{
					$z = '' ;
					if ( $fc != '+' )
					{
						$tra = array_pop ( $ltr ) ;
						if ( !array_pop ( $tr ) ) $z = "\n" ; // has been: "\n"
						array_push ( $tr , true ) ;
						array_push ( $ltr , '' ) ;

						// current-row-cell
						$cellcount_current[$in_table]++;
						

						array_pop ( $has_opened_tr );
						array_push ( $has_opened_tr , true ) ;

					}

					$l = array_pop ( $ltd ) ;
					//heading cells and normal cells are equal in LaTeX:
					if ( ($fc == '|' || $fc == '!') && !$firstCellOfRow) $l = ' & ';
					else if ( $fc == '+' ) {
						$ltx_caption .= $theline;
						continue; //Missing support for caption here!
					}
					else $l = '' ;
					//$firstCellOfRow = false;
					array_push ( $ltd , $l ) ;

					# Cell parameters
					$y = explode ( '|' , $theline , 2 ) ;
					# Note that a '|' inside an invalid link should not
					# be mistaken as delimiting cell parameters
					if ( strpos( $y[0], '[[' ) !== false ) {
						$y = array ($theline);
					}
					
					if ( count ( $y ) == 1 ) {
                        $y[0] = $this->fixContentforTableCells($y[0]);
						if ($fc == '!') { //Heading cell highlighting
                            
							$y = "{$z}{$l}" . "\\textbf{" . "{$y[0]}}" ;
						} else {
							$y = "{$z}{$l}{$y[0]}" ;
						}
                    } else {
						$attributes = $this->unstripForHTML( $y[0] );

						$multi_col = $this->checkColspan($attributes);
						
						//$y = "{$z}<{$l}".Sanitizer::fixTagAttributes($attributes, $l).">{$y[1]}" ;
						if ( $firstCellOfRow == false ) {
							$addSep = '&';
						} else {
							$addSep = '';
						}
						$y="{$z}".$addSep.'\multicolumn{'.$multi_col['colspan'].'}{'.$multi_col['latexfmt'].'}{'.$y[1].'}';
					}
					$firstCellOfRow = false; // was some lines up...
					$t[$k] .= $y;
					$anyCells = true;
				}
			}
		}

		$t = implode ( "\n" , $t ) ;
		# special case: don't return empty table
		//if(!$anyCells) $t = '';
		//$t .= trim($ltx_caption);
		return $t;
	}
	function checkColspan($str) {
		// just a test now
		$result = array();
		$attr = $this->parseAttrString($str);
		
		if ( array_key_exists('colspan', $attr) ) {
			$result['colspan'] = $attr['colspan'];
		} else {
			return false;	
		}
		
		if ( array_key_exists('latexfmt', $attr) ) {
			$result['latexfmt'] = $attr['latexfmt'];
		} else {
			$result['latexfmt'] = '|l|';
		}

		return $result;
    }
    function fixContentForTableCells($content) {
        $this->debugMessage('Zelleninhalt', $content );
        $content = str_replace('<br>', '\newline ', $content);
        return $content;
    }

	private function stripComments( $text = '' ) {
		$fName = __METHOD__;
		$this->profileIn(__METHOD__);
		/* strips out Mediawiki-comments, which are in fact HTML comments */
		$mode = '';
    		// This approach is from mediawiki
		while ( ($start = strpos($text, '<!--')) !== false ) {
			$end = strpos($text, '-->', $start + 4);
			if ($end === false) {
				# Unterminated comment; bail out
				break;
			}

			$end += 3;

			# Trim space and newline if the comment is both
			# preceded and followed by a newline
			$spaceStart = max($start - 1, 0);
			$spaceLen = $end - $spaceStart;
			while (substr($text, $spaceStart, 1) === ' ' && $spaceStart > 0) {
				$spaceStart--;
				$spaceLen++;
			}
			while (substr($text, $spaceStart + $spaceLen, 1) === ' ')
				$spaceLen++;
			if (substr($text, $spaceStart, 1) === "\n" and substr($text, $spaceStart + $spaceLen, 1) === "\n") {
				# Remove the comment, leading and trailing
				# spaces, and leave only one newline.
				$text = substr_replace($text, "\n", $spaceStart, $spaceLen + 1);
			}
			else {
				# Remove just the comment.
				$text = substr_replace($text, '', $start, $end - $start);
			}
		} // bis hierher
		$this->profileOut($fName);
		return $text;
	}

	private function doHTML($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		// First step only. Needs to be far more complex!!!
		// For some HTML-Tag-support

		$replacing = array(
			'<center>'      => '\begin{center}',
			'</center>'     => '\end{center}',
			"<i>"           => '\textit{',
			"</i>"          => '}',
			"<b>"           => '\textbf{',
			"</b>"          => '}',
			"<strong>"      => '\textbf{',
			"</strong>"     => '}',
			'<em>'          => '\textit{',
			'</em>'         => '}',
			"<tt>"          => '\texttt{',
			"</tt>"         => '}',
			'<br/>'         => '\\\\',
			'<br>'          => '\\\\',
			'<br />'        => '\\\\',
			'<small>'       => '{\small ',
			'</small>'      => '}',
			'<big>'         => '{\large ',
			'</big>'        => '}',
			'<blockquote>'  => '\begin{quotation}',
			'</blockquote>' => '\end{quotation}',
			"<sup>"         => '\textsuperscript{',
			"</sup>"        => '}',
			"<sub>"         => '\textsubscript{',
			"</sub>"        => '}',
			"<u>"           => '\underline{',
			"</u>"          => '}',
			'<code>'        => '\begin{verbatim}',
			'</code>'       => '\end{verbatim}',
		);
		wfRunHooks('w2lHTMLReplace', array(&$this, &$replacing, &$str));
		$str = str_ireplace(array_keys($replacing), array_values($replacing), $str);
		
		$this->profileOut($fName);
		return $str;
	}
	
	function doDivAndSpan($str, $t = 'span') {
		/*
		$span_data['w2l-remove'] = array (
			'before' => '',
			'after'  => '',
			'filter' => 'w2lRemove',
			'callback' => '',
			'string' => '',
			'environment' => '',
		); array($this, 'doSpanAndDivReplace')
		*/

		$this->DS_tag    = $t;
		

		//foreach ( $tag_data as $class => $values ) {
		//	$this->DS_class = $class;
		//	$this->DS_values = $values;
			$str = preg_replace_callback('/(<'.$t.'(.*)>(.*)<\/'.$t.'>)/sU', array($this, 'doSpanAndDivReplace'), $str);
		//}
		//$str = preg_replace('/<div class="w2l-remove(.*)>(.*)<\/div>/smU','', $str);
		//$str = strtr($str, $abbr);
		
		return $str;
	}
	
	function doSpanAndDivReplace($matches) {
		//$str = preg_match('/<'.$t.'(.*)class=\\\\dq\{\}(.*)'.$class.'(.*)\\\\dq\{\}(.*)>(.*)<\/'.$t.'>/sU', array($this, 'doSpanAndDivReplace'), $str);
		//wfVarDump($matches);
		$t = $this->DS_tag;
		$tag_data = $this->getVal($t);
		
		
		$full_block = trim(str_replace('\\dq{}','"', $matches[0]));
		$attributes = trim(str_replace('\\dq{}','"', $matches[2]));
		$content    = $matches[3];
		
		if ( !is_array($tag_data) ) {
			return $content;
		}
		
		foreach ($tag_data as $class => $values) {
			$match = array();
			if ( strpos($attributes, $class ) ) {
				// class is in here :)


				
				preg_match('/<'.$t.'(.*)class="(.*)'.$class.'(.*)"(.*)>(.*)<\/'.$t.'>/sU', $full_block, $match);

				//$content = $match[5];
				
				$other_classes = trim($match[2].' '.$match[3]);
				$otther_attr = trim($match[1].' '.$match[4]);
				$result = '';
				if ( isset($values['callback']) && $values['callback'] != '' ) {
					// Callback
					if ( is_callable($values['callback']) ) {
						$result = call_user_func_array($values['callback'], array(&$this, $content, $t, $other_classes, $match[0]));

					} else {
						return $content;
					}
			
				} elseif ( isset($values['string']) ) {
					// String
			
					if ( isset( $values['filter']) && is_callable($values['filter']) ) {
						$content = call_user_func_array($values['filter'], array(&$this, $content, $t, $other_classes));
					}
			
					$result = str_replace('%content%', $content, $values['string']);
			
				} elseif ( isset($values['environment']) && $values['environment'] != '') {
					//environment

					$result = '\begin{'.$values['environment'].'}';
					if ( isset( $values['filter']) && is_callable($values['filter']) ) {
						$result .= call_user_func_array($values['filter'], array(&$this, $content, $t, $other_classes));
						$this->debugMessage('Filter used:', $values['filter']);
					} else {
						$result .= $content;
					}
			
					$result .= '\end{'.$values['environment'].'}';
				} else { 
					// before/after/filter
					if ( isset( $values['before']) ) {
						$result .= $values['before'];
					}
			
					if ( isset( $values['filter']) && function_exists($values['filter']) ) {
						$result .= call_user_func_array($values['filter'], array(&$this, $content, $t, $other_classes));
					} else {
						$result .= $content;
					}
			
					if ( isset( $values['after']) ) {
						$result .= $values['after'];
					}
				}
				return $result;
			}
		}
		
		// This would be where we would find a span or div with just a style or nothing 
		// or an unknown class; We don't transform them for now :(
		
		return $content;
	}
	
	/* Toolkit functions */
	private function uniqueString() {
		return dechex(mt_rand(0, 0x7fffffff)) . dechex(mt_rand(0, 0x7fffffff));
	}
	/* Profiling and debugging functions */
	private function profileIn($fName) {
		if ($this->doProfiling) {
			$time = microtime();
			$this->ProfileLog[] = array("function"=>$fName, "time"=>$time, "type" => "in");
		}
		return;
	}
	private function profileOut($fName) {
		if ($this->doProfiling) {
			$time = microtime();
			$this->ProfileLog[] = array("function"=>$fName, "time"=>$time, "type" => "out");
		}
		return;
	}
	private function profileMsg($msg) {
		if ($this->doProfiling) {
			$time = microtime();
			$this->ProfileLog[] = array("function"=>$msg, "time"=>$time, "action" => "msg");
		}
		return;
	}

	public function maskLaTeX($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$latex = array(
			'LaTeX'    => '\LaTeX{}',
			'TeX'      => '\TeX{}',
			'LaTeX 2e' => '\LaTeXe{}'
		);
		$str = strtr($str, $latex);
		$this->profileOut($fName);
		return $str;
	}

	public function maskLatexCommandChars($str) {

		// Chars, which are important for latex commands:
		// {,},\,&
		$this->Et = $this->getMark("Et");
		$this->sc['backslash'] = $this->getMark('backslash');
		
		$this->mask($this->Et, '\&');
		$this->mask($this->sc['backslash'], '\\textbackslash ');
		
		$chars = array(
			'\\' => $this->sc['backslash'],
			"{" => "\{",
			"}" => "\}",
			'&' => $this->Et,
			'^' => '\textasciicircum{}',
		);
		$str = strtr($str, $chars);

		return $str;
	}

	public function maskMwSpecialChars($str) {

		// Special chars from mediawiki:
		// #,*,[,],{,},|
		$chars = array(
			'#' => "\#",
			"*" => "\(\ast{}\)",
		);
		$str = strtr($str, $chars);

		return $str;
	}

	public function maskLatexSpecialChars($str) {

		// _,%,§,$,&,#,€,
		$chars = array(
			'_' => '\_',
			'%' => '\%',
			'$' => '\$',
		);
		
		$str = strtr($str, $chars);
		

		return $str;
	}



	public function processCurlyBraces($str) {
		$fName = __METHOD__;
		$this->profileIn($fName);
		$new_str = '';
		if ($this->initiated == false ) {
			$this->initParsing();
		}

		++$this->curlyBraceDebugCounter;
		$this->curlyBraceLength = $this->curlyBraceLength + strlen($str);
		//$this->reportError($str, __METHOD__);
		// This function processes all templates, variables and parserfunctions
		$marker = $this->getMark('pipe');// $this->uniqueString();
		//$str = preg_replace('/\[\[(.*)\|(.*)\]\]/U', '[[$1'.$marker.'$2]]', $str);
		$test = $this->split_str($str);

		foreach($test as $part) {
			// if first
			if (substr($part, 0,2 ) == '{{' ) {
				//$part = preg_replace('/\[\[(.*)\|(.*)\]\]/U', '[[$1'.$marker.'$2]]', $part);

				$match[0] = $part;
				$match[1] = substr($part, 2, -2);
				//$this->reportError($match[0], __METHOD__);#
				//$this->reportError($match[1], __METHOD__);#

				$part = $this->doCurlyBraces($match);
				//$part = str_replace($marker, '|', $part);
			}
			$new_str .= $part;

		}

		//$str = preg_replace_callback('/\{\{(.*?)\}\}/sm', array($this, 'doCurlyBraces'), $str);

		//$new_str = str_replace($marker, '|', $new_str);
		$chars = array('\{\{\{' => '{{{', '\}\}\}' => '}}}');
		$new_str = strtr($new_str, $chars);
		$this->profileOut($fName);
		return $new_str;
	}

	private function doCurlyBraces($matches) {
		$orig  = $matches[0];
		$match = $matches[1];
		//%%
		unset($matches);
		//$this->reportError($match, __METHOD__);
		$args = array();
		//$match = strtr($match, array("\n"=>""));
		$match = trim($match);


		// new
		if ( substr_count($match, '|') !== 0 ) {
			$tmp = explode('|', $match, 2);
			$identifier = $tmp[0];
			$args = $tmp[1];
		} else {
			$identifier = $match;
			$args = '';
		}
		$tmp = '';
		$type = $this->checkIdentifier($identifier);
		//$this->reportError($identifier."->".$type, __METHOD__);

		switch ($type) {
			case W2L_TEMPLATE:
				if ( '' == $args ) {
					// no arguments
					$args = array();
				}
				$args = $this->processArgString($args);
				// check the name

				$tmp = $this->getContentByTitle($identifier, NS_TEMPLATE);
				//$this->reportError(strlen($tmp), __METHOD__);
				$tmp = $this->preprocessString($tmp);
				//$this->reportError(strlen($tmp), __METHOD__);
				$tmp = $this->processTemplateVariables($tmp, $args);

				//$this->reportError(strlen($tmp), __METHOD__);
				$tmp = $this->processCurlyBraces($tmp);
			break;
			case W2L_PARSERFUNCTION:
				$identifier = substr($identifier, 1);
				// Now falling through, as the code ist the same now:
			case W2L_COREPARSERFUNCTION:
				$fnc = explode(':', $identifier, 2);
				$expr = $fnc[1];
				$function = $fnc[0];
				$mark = $this->getMark('pipe');

				$args = preg_replace('/\{\{\{(.*)\|(.*)\}\}\}/U', '{{{$1'.$mark.'$2}}}', $args);
				$args = $this->processCurlyBraces($args);
				$args = preg_replace('/\[\[(.*)\|(.*)\]\]/U', '[[$1'.$mark.'$2]]', $args);
				$args = explode('|',$args);// ((>)|(<))
				$new_args = array();
				foreach ($args as $value) {
					$value = str_replace($mark, '|', $value);
					$new_args[] = $value;
				}
				//%%
				unset($args);
				$tmp = $this->processParserFunction($function, $expr, $new_args);
			break;
			case W2L_TRANSCLUSION:
				if ( '' == $args ) {
					// Not sure, why this has been introduced. Commenting out the array-fication, for this causes a warning...
					// no arguments
					$args = array();
				}
				$title = substr($identifier, 1);
				$args = $this->processArgString($args);
				$tmp = $this->getContentByTitle($title);
				$tmp = $this->preprocessString($tmp);
				$tmp = $this->processTemplateVariables($tmp, $args);
				
				$tmp = $this->processCurlyBraces($tmp);
			break;
			case W2L_VARIABLE:
				$tmp = $this->mw_vars[$identifier];
			break;
			default:
				$tmp = $orig;
			break;
		}
		return trim($tmp);
	}

	private function processArgString($str) {
		$args = array();
		$tmp = array();
		if (is_array($str) ) {
			return $str;
		}
		$tmp = explode('|', $str);
		
		$current_arg = 0;
		foreach($tmp as $keyvaluepair) {
			++$current_arg;

			if (strpos($keyvaluepair, '=') !== false) {
				$keyvaluepair = explode('=', $keyvaluepair, 2);
				$key = trim($keyvaluepair[0]);
				//%%$value = trim($keyvaluepair[1]);

				$args[$key] = trim($keyvaluepair[1]);
			} else {
				$args[$current_arg] = $keyvaluepair;
			}
		}


		return $args;
	}

	private function processTemplateVariables($str, $args = array()) {
		// replace the content by the args...
		$this->templateVars = array();
		$this->templateVars = $args;
		$str = preg_replace_callback('/\{\{\{(.*?)\}\}\}/sm', array($this, 'doTemplateVariables'), $str);
		$chars = array('{{{'=>'\{\{\{', '}}}' => '\}\}\}');
		$str = strtr($str, $chars);
		//%% This could break nested templates... We'll see
		unset($this->templateVars);
		return $str;
	}
	private function doTemplateVariables($match) {
		// replace the content by the args...

		if ( substr_count($match[1],'|') ) {
			$with_default = explode('|', $match[1], 2);

			//%% $content = $this->templateVars[$with_default[0]];

			if ( empty($this->templateVars[$with_default[0]]) ) {
				return $with_default[1];
			} else {
				return $this->templateVars[$with_default[0]];
			}
		} else {
			//%% $content = $this->templateVars[$match[1]];

			if ( empty($this->templateVars[$match[1]]) ) {
				return $match[0];
			} else {
				return $this->templateVars[$match[1]];
			}
		}

	}

	private function processParserFunction($fnc, $expr, $args) {

		$params = array($this, trim($expr));
		foreach($args as $value) {
			$params[] = trim($value);
		}
		unset($args);

		if ( array_key_exists($fnc , $this->pFunctions) ) {
			$content = call_user_func_array($this->pFunctions[$fnc], $params);
			if ( is_array($content) ) {
				return '';
			}
			return $content;
		} else {
			//%% old line:
			//return '{{#'.$fnc.':'.$expr.'|'.implode('|', $args).'}}';
			return '{{#'.$fnc.':'.$expr.'|'.implode('|', $params).'}}';
		}

	}

	private function split_str($str) {
		//
		$table_open_mark  = $this->getMark('table-open');
		$table_close_mark =  $this->getMark('table-close');

		$str = str_replace("\n{|", $table_open_mark, $str);
		$str = str_replace("|}\n", $table_close_mark, $str);

		$before_last_char = '';
		$last_char = '';
		$cur_char = '';
		$cb_counter = 0;
		$char_counter = 0;
		$split_array = array();
		$block = 0;
		$split_array[$block] = '';
		$in_block = false;

		$tmp_char = str_split($str);

		foreach($tmp_char as $cur_char) {
			//
			//$cur_char = $str{$char_counter};

			switch ($cur_char) {
				case '{':
					++$cb_counter;
					if ($cb_counter == 1) {
						++$block;
						$split_array[$block] = '';
						$split_array[$block] .= $cur_char;

					} else {
						$split_array[$block] .= $cur_char;
					}
				break;
				case '}':
					--$cb_counter;
					if ($cb_counter == 0) {
						$split_array[$block] .= $cur_char;

						++$block;
						$split_array[$block] = '';


					} else {
						$split_array[$block] .= $cur_char;
					}
				break;
				default:
					$split_array[$block] .= $cur_char;
				break;
			}

			$before_last_char = $last_char;
			$last_char = $cur_char;
			++$char_counter;
			//if ( !isset($str{$char_counter}) ) {


		//		break;
			//}
		}

		foreach ($split_array as $key => $value) {
	  		$value = str_replace( $table_open_mark,"\n{|", $value);
	  		$value = str_replace( $table_close_mark, "|}\n", $value);
	  		$new_split[$key] = $value;
		}

		return $new_split;
	}

	public function getContentByTitle( $title_str , $namespace = NS_MAIN) {
		$title_str =  trim($title_str);

		$title = Title::newFromText( $title_str , $namespace);

		if ( !is_a($title, 'Title') ) {
			$text = $title_str;
			$this->reportError("title_str=".$title_str, __METHOD__);
			return $text;
		}
		
		if ( !$title->UserCanRead() ) {
			return '';
		}
		
		if ( $title->exists() ) {
			$rev  = new Article( $title, 0 );
			$text = $rev->getContent();
		} else {
			$text = $title_str;
		}
		
		return $text;
	}
	
	public function checkIdentifier($str) {
		$str = trim($str);
		if ( array_key_exists($str, $this->mw_vars) )
			return W2L_VARIABLE;

		if ( '#' == $str{0} ) {
			$pf = explode(':', $str, 2);
			$pf = substr($pf[0], 1);
			if ( array_key_exists($pf, $this->pFunctions) == true) {
				return W2L_PARSERFUNCTION;
			} else {
				return false;
			}
			
		}
		if ( ':' == $str{0} )
			return W2L_TRANSCLUSION;
		
		$test = explode(':', $str, 2);
		//$this->reportError($test[0], __METHOD__);
		//$this->reportError(array_key_exists($test[0], $this->pFunctions), __METHOD__);
		if ( array_key_exists($test[0], $this->pFunctions) == true)
			return W2L_COREPARSERFUNCTION;

		return W2L_TEMPLATE;
	}

	public function reportError( $msg, $fnc ) {
		$this->error_msg[] = $fnc.': '.$msg."\n";
		$this->is_error = true;
	}

	public function getErrorMessages() {
		if ( $this->is_error == true) {
			$errors  = wfMsg('w2l_parser_protocol')."\n";
			$errors .= '<pre style="overflow:auto;">';
			foreach ($this->error_msg as $error_line) {
				$errors .= $error_line;
			}
			$errors .= '</pre>'."\n";
			return $errors;
		} else {
			return '';
		}
	}

	public function setMwVariables($vars) {
		$this->mw_vars = $vars;
		return true;
	}
	
	public function addPackageDependency($package, $options = '') {
		$this->required_packages[$package] = $options;
		return true;
	}
	
	public function addLatexHeadCode($code) {
		$this->latex_headcode[] = $code;
		return true;
	}
	public function getLatexHeadCode() {
		$code = array_unique($this->latex_headcode);
		return trim(implode("\n", $code));
	}
	public function getUsePackageBlock() {
		$packages = '';
		foreach($this->required_packages as $package => $options) {
			$packages .= '\usepackage';
			if ( $options != '' ) {
				$packages .= '['.$options.']';
			}
			$packages .= '{'.$package.'}'."\n";
		}
		return trim($packages)."\n";
	}

	function parseAttrString($str) {
		$result = array();
		$con = true;
		$i = 1;
		while ($con == true) {
			$search_char = ' =';
			$str = trim($str);
			if ( empty($str) ) {
				$con = false;
				continue;
			}
			if ($i>10000) {
				$con = false;
				continue;
			}
			$str = $str.' ';
			// search for attributename...
			$howmany = strcspn($str, $search_char);
			$attr = substr($str, 0, $howmany);
			$str = substr($str, $howmany);
			
			// get value
			$attr_value = '';
			$fChar = $str{0};
			if ( $fChar == '=' ) 
				$str = substr($str, 1);
			$fChar=$str{0};
			
			if ( $fChar == '"' ) {
				// next to search for is "
						$search_char = '"';
				$str = substr($str, 1);
				$howmany = strcspn($str, $search_char);
				$attr_value = substr($str, 0, $howmany);
				$str = substr($str, ++$howmany);
			} elseif ( $fChar == "'" ) {
				$search_char = "'";
				$str = substr($str, 1);
				$howmany = strcspn($str, $search_char);
				$attr_value = substr($str, 0, $howmany);
				$str = substr($str, ++$howmany);
			} elseif ($fChar== ' ') {
				$attr_value = '';
			} else {
				$search_char = ' ';
				//$str = substr($str, 1);
				$howmany = strcspn($str, $search_char);
				$attr_value = substr($str, 0, $howmany);
				$str = substr($str, ++$howmany);
			}
			// save it to the array
			$result[$attr] = $attr_value;
			//%%
			unset($attr_value);
			$i++;
		}
		return $result;
	}
	
	function debugMessage($caller, $message) {

		$this->debug[] = array('caller' => $caller, 'msg' => $message);
		return true;
	}
	
	function getDebugMessages() {
		$messages = '';
		
		foreach ($this->debug as $msg ) {
			$error = print_r($msg['msg'], true);
			$messages .= '<div>'.$msg['caller'].' says: <pre>'.htmlspecialchars($error).'</pre></div>';
		}
		
		if ( '' != $messages ) {
			return '<div class="w2l-debug">'.$messages.'</div>';
		} else {
			return 'no errors discoverd';
		}
	}

	public function getMark($tag, $number = -1) {
		// This function takes strings, which are to be inserted in verabtimenv,
		// like links, f.e.
		// returns a marker

		++$this->marks_counter;
		if ( $number == -1 ) {
			$number = $this->marks_counter;
		}
		$marker = '((UNIQ-W2L-'.$this->unique.'-'.$tag.'-'.sprintf('%08X', $number).'-QINU))';

		return $marker;
	}

	function mask($key, $value) {
		$this->mask_chars[$key] = $value;
	}

	function deMask($str) {
		$str = str_replace(array_keys($this->mask_chars), array_values($this->mask_chars), $str);
		return $str;
	}

/**
 * This function combines getMark() and mask().
 * Better use this function in extensions and stuff
 * Though in special circumstances you should not
 *
 * @param string $content
 *  The contetnt to be masked 
 * @param string $type
 *  The type of the content
 * @retval string
 *  Returns a marker to be inserted into articles
 */
	function getMarkerFor($content, $type = 'UNKNOWN') {
		$marker = $this->getMark($type);
		$this->mask($marker, $content);
		return $marker;
	}

	function getPerPageDirectives($text) {
		$matches = array();
		$count   = 0;
		$expr    = '/__([A-Z_])*__/';

		$count = preg_match_all( $expr, $text, $matches);

		foreach ( $matches[0] as $directive ) {
			$this->directives[$directive] = true;
			$this->debugMessage( 'GetPerPageDirectives', $directive );
		}
		$text = str_replace( array_keys($this->directives), "", $text );
		return $text;
	}

	function hasDirective($directive) {
		if ( isset( $this->directives[$directive] ) && $this->directives[$directive] == true ) {
			return true;
		}
		return false;
	}

	// Functions regarding sorting and Bibtex
	function requireBibtex()  { $this->run_bibtex = true; }
	function requireSorting() { 
		$this->run_sort = true;
		$this->addPackageDependency('makeidx');
		return true;
	}
	function getBibtexState() { return $this->run_bibtex; }
	function getSortState()   { return $this->run_sort;   }

	// Wiki-Parser functions
	function &getTitle() { return $this->mTitle; }
	function disableCache() {
		return true;
	}
	
}

