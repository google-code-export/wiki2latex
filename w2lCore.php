<?php

/*
 * File: w2lCore.php
 *
 * Purpose:
 * Contains the main class, which provides the export-functionality to Mediawiki
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

class Wiki2LaTeXCore {

	var $options = array();

	var $messagesLoaded = false;

	var $version     = W2L_VERSION;
	var $required_mw = '1.11';
	var $parserParams = array();

	function Wiki2LaTeX() {
		$this->__construct();
	}

	function __construct() {
		global $w2lConfig;
		$this->config =& $w2lConfig;
		return;
	}
	
	public function onUnknownAction($action, &$article) {
		// Here comes all the stuff to show the form and parse it...
		global $wgTitle, $wgOut, $wgUser;
		
		$action = str_replace('w2l', '', $action);
		$action = 'on'.ucfirst(strtolower($action));

		// Call appropriate method
		if ( method_exists( $this, $action ) ) {
			// Mediawiki objects
			$this->mArticle  =& $article;
			$this->mTitle    =& $wgTitle;
			$this->mUser     =& $wgUser;
			$this->mValues   = new webRequest();
			$this->mRevision = $this->mArticle->getRevisionFetched();

			// Wiki2LaTeX objects
			$this->Parser = new Wiki2LaTeXParser;
			$options      = $this->getParserConfig();
			$this->Parser->setConfig($options);

			$this->Parser->initParsing();

			$this->mwVars = $this->prepareVariables(); // mem
			$this->Parser->setMwVariables($this->mwVars);

			$success = $this->$action();

			return false;
		} else {
			return true;
		}
	}
	
	// These Methods to do the work we have to do
	private function onLatexform( $msg_add = '' ) {
		global $wgOut, $wgScriptPath, $wgExtraNamespaces, $wgUser, $w2lLanguages;

		if ( $this->config['auto_clear_tempfolder'] == true ) {
			$cl_temp = $this->clearTempFolder();
		}
		
		$title     = $this->mTitle->getEscapedText();
		$url_title = $this->mTitle->getPrefixedDBkey();
		$namespace = $this->mTitle->getNamespace();

		$select['template'] = $this->config['default_template'];

		if ( isset($this->config['defaults']) ) {
			foreach ($this->config['defaults'] as $def_line) {
				if ( preg_match('/'.preg_quote($def_line['search']).'/', $title) ) {
					$select['template'] = $def_line['template'];
				}
			}
		}
		
		$output = '';

		// Show a message, if form is called by a redirect from deleting all temp-files
		if ( $msg_add != '' ) {
			$output .= $msg_add;
		}

		$output .= '<form method="post" action="'.$wgScriptPath.'/index.php">'."\n";
		$output .= '<input type="hidden" name="title" value="'.$url_title.'" />'."\n";
		$output .= '<input type="hidden" name="started" value="1" />'."\n";
		
		$fieldsets = array();
		
		$export_options['legend'] = wfMsg('w2l_select_output');
		$export_options['html'] = '<button type="submit" name="action" value="w2ltextarea">'.wfMsg('w2l_select_textarea').'</button>';
		$export_options['html'] .= '<button type="submit" name="action" value="w2ltexfiles">'.wfMsg('w2l_select_texfiles').'</button>';

		if (true == $this->config['pdfexport']) {
			$export_options['html'] .= '<button type="submit" name="action" value="w2lpdf">';
			$export_options['html'] .= wfMsg('w2l_select_pdf','pdf');
			$export_options['html'] .= '</button>';
		}

		// Exportoptionen
		$field_opt  = '<label>Documentclass: <select name="documentclass" id="documentclass">';
		$field_opt .= '<option value="book">'.wfMsg('w2l_select_docclass_book').'</option>'."\n";
		$field_opt .= '<option value="report">'.wfMsg('w2l_select_docclass_report').'</option>'."\n";
		$field_opt .= '<option value="article" selected="selected">'.wfMsg('w2l_select_docclass_article').'</option>'."\n";
		$field_opt .= '</select></label><br/>';
		
		$field_opt .= '<select name="process_curly_braces" id="process_curly_braces">';
		$field_opt .= '<option value="0">'.wfMsg('w2l_select_removetemplates').'</option>'."\n";
		$field_opt .= '<option value="1">'.wfMsg('w2l_select_donotprocesstemplates').'</option>'."\n";
		$field_opt .= '<option value="2" selected="selected">'.wfMsg('w2l_select_processtemplates').'</option>'."\n";
		$field_opt .= '</select>'."\n";
		$fieldsets[100] = array('legend' => wfMsg('w2l_options'), 'html' => $field_opt );
		// Language for Babel:
		
		$field_babel = '<label>'.wfMsg('w2l_select_babel_language').': ';
		$field_babel .= '<select name="babel">';
		$babel_default = $wgUser->getOption('w2lBabelDefault');
		foreach( $w2lLanguages as $key => $value ) {
			$field_babel .= '<option value="'.$value.'"';
			if ($babel_default == $value) {
				$field_babel .= ' selected="selected"';
			}
			$field_babel .= '>'.$key.'</option>'."\n";
		}
		
		$field_babel .= '</select></label><br/>'."\n";
		
		$fieldsets[200] = array('legend' => wfMsg('w2l_select_babel_language'), 'html' => $field_babel);
		

		// Fieldset für Templates:
		$templ_field = '';

		$templ_field .= '<label>';
		$templ_field .= wfMsg('w2l_select_template');
		$templ_field .= ' <select name="template">'."\n";
		if ($select['template'] == ('auto' || 'magic') ) {
			$templ_field .= '<option value="auto" selected="selected">(Magic)</option>'."\n";
		} else {
			$templ_field .= '<option value="auto">(Magic)</option>'."\n";
		}

		if ($select['template'] == 'empty' ) {
			$templ_field .= '<option value="empty" selected="selected">(Empty)</option>'."\n";
		} else {
			$templ_field .= '<option value="empty">(Empty)</option>'."\n";
		}

		// Auswahl des Templates...
		if ( !is_array($wgExtraNamespaces) ) {
			$LaTeX_namespace = false;
		} else {
			$LaTeX_namespace = array_search('LaTeX', $wgExtraNamespaces);
		}
		
		if ( $LaTeX_namespace !== false ) {
			$tables = 'page';
			$vars   = array('page_title', 'page_id');
			$conds  = array("page_namespace = ".$LaTeX_namespace, "page_title Like 'W2L_%'", 'page_is_redirect = 0');
			$db     = wfGetDB(DB_SLAVE);
			$result = $db->select($tables, $vars, $conds);
			while ($line = $db->fetchRow( $result )) {
				$tmpl_name = str_replace("W2L_", "", $line['page_title']);
				$tmpl_name = str_replace("_", " ", $tmpl_name);
				$select_it = 0;

				if ($tmpl_name == $select['template']) {
					$templ_field .= '<option value="'.$line['page_id'].'" selected="selected">'.$tmpl_name.'</option>'."\n";
				} else {
					$templ_field .= '<option value="'.$line['page_id'].'">'.$tmpl_name.'</option>'."\n";
				}
			}
			$db->freeResult($result);
		}

		$templ_field .= "</select></label>";

		$fieldsets[300] = array('legend' => wfMsg('w2l_select_templates_heading'), 'html' => $templ_field);
		
		$compat_form = '';
		$compat_legend = 'Options';
		wfRunHooks('w2lFormOptions', array( &$this, &$compat_form ));
	
		if ( $compat_form != '' ) {
			$fieldsets[400] = array('legend' => $compat_legend, 'html' => $compat_form);
		}
		
		$at_form = '';
		$at_legend = 'Magic-Template-Options';
		wfRunHooks('w2lMagicTemplateOptions', array( &$this, &$at_form ));
		if ( $at_form != '' ) {
			$fieldsets[500] = array('legend' => $at_legend, 'html' => $at_form);
		}
		$fieldsets[10] = $export_options;
		$fieldsets[1000] = str_replace('checked="checked"', '',$export_options);
		wfRunHooks('w2lFormFieldsets', array(&$this, &$fieldsets) );
		ksort($fieldsets);
		foreach ($fieldsets as $fieldset) {
			$output .= '<fieldset>'."\n";
			$output .= '<legend>'.$fieldset['legend'].'</legend>'."\n";
			$output .= $fieldset['html'];
			$output .= '</fieldset>'."\n";
		}     

		$output .= $this->getFolderLinks();
		$output .= '</form>'."\n";

		$wgOut->addHTML($output);

		$wgOut->setPagetitle( wfMsg('w2l_export_title', $title) );
		$wgOut->setSubtitle(  wfMsg('w2l_export_subtitle', $title) );

		return true;
	}
        
	private function onTexfiles() {
		$this->onPdf(false);
		return true;
	}

	private function onTextarea() {
		global $wgOut, $wgUser;

		$title = $this->mTitle->getEscapedText();

		$to_parse = $this->mArticle->getContent();
		$parsed = $this->Parser->parse($to_parse, $this->mTitle, W2L_STRING);

		$output = '<textarea style="height:200px;">'.htmlspecialchars($parsed).'</textarea>';
		$output .= $this->Parser->getErrorMessages();

		if ( $wgUser->getOption('w2lDebug') == true ) {
			$output .= wfMsg('w2l_debug_info', round($this->Parser->getParseTime(), 3), $this->Parser->curlyBraceDebugCounter, $this->Parser->curlyBraceLength);
			$output .= $this->Parser->getDebugMessages();
			$output .= '<div>Memory-Peak: '.sprintf("%.2f",((memory_get_peak_usage() / 1024) / 1024 )).' MB</div>';
		}
		$wgOut->addHTML($output);

		$wgOut->setPagetitle( wfMsg('w2l_result_title', $title) );
		$wgOut->setSubtitle( wfMsg('w2l_result_subtitle', $title) );
		return true;
	}

	private function onPdf($compile = true) {
		global $wgOut, $wgLang, $IP, $wgScriptPath, $wgUser;

		if ( ($this->config['pdfexport'] == false) AND ($compile == true) ) {
			// pdf export is not allowed, so don't export
			$wgOut->addHTML(wfMsg('w2l_pdfexport_disabled'));
			return false;
		}

		$output   = '';
		$title    = $this->mTitle->getEscapedText();
		$to_parse = $this->mArticle->getContent();

		// Get Template-Vars...

		$template_vars = $this->getTemplateVars($to_parse);

		$template_vars = array_merge($template_vars, $this->mwVars);

		// If title was not set by a <templatevar> tag, use page title
		// Put some special variables in the template vars
		wfRunHooks( 'w2lTemplateVarsLaTeX', array(&$this, &$template_vars) );

		// we need that template-file...
		$temp_id = $this->mValues->getVal("template");

		$parsed = $this->Parser->parse($to_parse, $this->mTitle);

		if ( $temp_id == 'auto' ) {
			// create a template automagically,
			$template = $this->createMagicTemplate();
		} else if ( $temp_id == 'empty' ) {
			// Use empty for a complete page of LaTeX-Code
			$template  = '==Wiki2LaTeX\'s Empty Template=='."\n";
			$template .= '<latexfile name="Main">'."\n";
			$template .= '((W2L_CONTENT))'."\n";
			$template .= '</latexfile>';

		} else {
			$template = $this->getTemplate($temp_id);
		}
		$files = $this->createTemplateFiles($template);

		// Adding some Template-Variables...
		$template_vars['W2L_CONTENT'] =& $parsed; 
		$template_vars['W2L_VERSION'] = $this->version;

		// Now the template vars need to be put into ((var))
		$tpl_vars = array();
		foreach($template_vars as $tplv_key => $tplv_value) {
			$tpl_vars['(('.$tplv_key.'))'] = $tplv_value;
		}
		// define the path for the files...
		// Create temp-folder
		$tmpPiece = $this->getTempDirPiece();

		$compiler = new Wiki2LaTeXCompiler($tmpPiece, true);

		$compiler->addFiles($files);

		$file_suc = $compiler->generateFiles($tpl_vars);

		if ( (true == $compile) && ( true == $file_suc ) ) {
			// Get states
			$bibtex = $this->Parser->getBibtexState();
			$sort   = $this->Parser->getSortState();
			$compile_error = $compiler->runLaTeX('Main', $sort, $bibtex);
			$log    = $compiler->getLogFile();
		} else {
			$compile_error = true;
		}

		if ( $wgUser->getOption('w2lDebug') == true) {
			$output .= wfMsg('w2l_debug_info', $this->Parser->getParseTime(), $this->Parser->curlyBraceDebugCounter, $this->Parser->curlyBraceLength);
			//$output .= '<pre>'.htmlspecialchars($parsed).'</pre>';
			$output .= $this->Parser->getDebugMessages();
			$output .= '<div>Memory-Peak: '.sprintf("%.2f",((memory_get_peak_usage() / 1024) / 1024 )).' MB</div>';
		}
		$output .= $this->getFolderLinks();
		$wgOut->setPagetitle( wfMsg('w2l_result_title', $title) );
		$wgOut->setSubtitle( wfMsg('w2l_result_subtitle', $title) );
		$wgOut->addHTML( wfMsg('w2l_result_heading') );
		$title_fn = w2lWebsafeTitle($title);
		//wfVarDump($title_fn);
		if (false == $compile_error) {
			
			$wgOut->addHTML( wfMsg('w2l_result_folder', $wgScriptPath, $title_fn, $tmpPiece) );
			
			$wgOut->addHTML($this->Parser->getErrorMessages());

		} elseif ( $compile == false ) {
			$wgOut->addHTML( wfMsg('w2l_result_tex', $wgScriptPath, $title_fn, $tmpPiece));
		} else {
			$wgOut->addHTML('<p>'. wfMsg('w2l_latex_failed',$wgScriptPath, $title_fn, $tmpPiece). '</p>' );
		}
		$wgOut->addHTML( '<textarea style="height:200px">'.$compiler->getLog().'</textarea>' );
		if ( $wgUser->getOption('w2lShowParsed') == true ) {
			$wgOut->addHTML( '<h2>Parsed LaTeX-Code:</h2><textarea style="height:300px">'.htmlspecialchars($parsed).'</textarea>' );
		}
		if ( $wgUser->getOption('w2lShowLog') == true && $compile == true ) {
			$wgOut->addHTML( '<h2>Log file:</h2><textarea style="height:200px">'.$compiler->getLogFile().'</textarea>' );
		}

		$wgOut->addHTML( $output );

		return true;
	}

	function onCleartempfolder() {
		global $IP, $wgOut;

		$state = $this->clearTempFolder();
		if ($state) {
			$text = wfMsg('w2l_delete_success');
			$msg = $this->getStatusMessage($text, true);
		} else {
			$text = wfMsg('w2l_delete_error');
			$msg = $this->getStatusMessage($text, false);
		}
		$this->onLatexform($msg);
		return true;
	}
        
	function clearTempFolder() {
		$dir   = w2lTempDir();
		$state = $this->full_rmdir($dir, false);
		return $state;
	}
        
	// Our helperfunctions
	function full_rmdir( $dir, $del_dir = true, $del_files = false ) {
		// Code from:
		// http://de.php.net/manual/en/function.rmdir.php
		// By:
		// swizec at swizec dot com (31-Jul-2007 03:53)

		$state = true;

		if ( !is_dir($dir) ) {
			return false;
		}

		if ( !is_writable( $dir ) ) {
			if ( !@chmod( $dir, 0777 ) ) {
				return false;
			}
		}

		$d = dir( $dir );

		while ( false !== ( $entry = $d->read() ) ) {

			if ( $entry == '.' || $entry == '..' ) {
				continue;
			}

			if ( is_dir( "$dir/$entry" ) ) {
				if ( (substr( $entry, 0, 7 ) == "w2ltmp-")  ) {
					$state = $this->full_rmdir( "$dir/$entry", true, true );
				}
			} else {
				// a file...
				if ( true == $del_files ) {
					if ( !@unlink( "$dir/$entry" ) ) {
						$d->close();
						return false;
					}
				}
			}
		}

		$d->close();

		if ($del_dir) {
			$state = rmdir( $dir );
		}

		return $state;
	}

	function createMagicTemplate() {
		$packages = $this->Parser->getUsePackageBlock();
		$babel    = $this->Parser->getVal('babel');
		$code     = $this->Parser->getLatexHeadCode();
		$docClass = $this->Parser->getVal('documentclass');
		$docClassOptions = 'a4paper,12pt';
		wfRunHooks('w2lMagicTemplateCreate', array(&$this, &$docClassOptions) );
		// Magic Template will be in $tmpl;
		include( $this->config['magic_template'] );
		$tmpl = str_replace('((W2L_REQUIRED_PACKAGES))', $packages, $tmpl);
		$tmpl = str_replace('((W2L_HEAD))', $code, $tmpl);
		return $tmpl;
	}

	public function getTemplateVars( $text ) {
		$vars = array();

		$parts = explode('<templatevar vname="', " ".$text);
		array_shift($parts);
		foreach ($parts as $part) {
			$var = explode('">', $part);
			$var_name = $var[0];
			$tmp = explode("</templatevar>", $var[1], 2);
			$var_content = $tmp[0];
			$vars[$var_name] = $var_content;
		}

		return $vars;
	}

        public function prepareVariables() {

                global $wgContLang, $wgSitename, $wgServer, $wgServerName, $wgScriptPath;
                global $wgContLanguageCode;
                global $wgTitle;

                $this->mRevisionId = $this->mTitle->getLatestRevID();

                $ts = time();

                # Use the time zone
                global $wgLocaltimezone;
                if ( isset( $wgLocaltimezone ) ) {
                        $oldtz = getenv( 'TZ' );
                        putenv( 'TZ='.$wgLocaltimezone );
                }

                wfSuppressWarnings(); // E_STRICT system time bitching
                $localTimestamp = date( 'YmdHis', $ts );
                $localMonth = date( 'm', $ts );
                $localMonthName = date( 'n', $ts );
                $localDay = date( 'j', $ts );
                $localDay2 = date( 'd', $ts );
                $localDayOfWeek = date( 'w', $ts );
                $localWeek = date( 'W', $ts );
                $localYear = date( 'Y', $ts );
                $localHour = date( 'H', $ts );
                if ( isset( $wgLocaltimezone ) ) {
                        putenv( 'TZ='.$oldtz );
                }
                wfRestoreWarnings();

                // some simpler ones...

                $w2lVars = array(
                        'currentmonth' =>  $wgContLang->formatNum( gmdate( 'm', $ts ) ),
                        'currentmonthname' => $wgContLang->getMonthName( gmdate( 'n', $ts ) ),
                        'currentmonthnamegen'=> $wgContLang->getMonthNameGen( gmdate( 'n', $ts ) ),
                        'currentmonthabbrev'=> $wgContLang->getMonthAbbreviation( gmdate( 'n', $ts ) ),
                        'currentday'=> $wgContLang->formatNum( gmdate( 'j', $ts ) ),
                        'currentday2'=> $wgContLang->formatNum( gmdate( 'd', $ts ) ),
                        'localmonth'=>$wgContLang->formatNum( $localMonth ),
                        'localmonthname' => $wgContLang->getMonthName( $localMonthName ),
                        'localmonthnamegen'=> $wgContLang->getMonthNameGen( $localMonthName ),
                        'localmonthabbrev'=> $wgContLang->getMonthAbbreviation( $localMonthName ),
                        'localday'=> $wgContLang->formatNum( $localDay ),
                        'localday2' => $wgContLang->formatNum( $localDay2 ),
                        'pagename' => wfEscapeWikiText( $this->mTitle->getText() ),
                        'pagenamee'=>$this->mTitle->getPartialURL(),
                        'fullpagename' =>wfEscapeWikiText( $this->mTitle->getPrefixedText() ),
                        'fullpagenamee' => $this->mTitle->getPrefixedURL(),
                        'subpagename' => wfEscapeWikiText( $this->mTitle->getSubpageText() ),
                        'subpagenamee' => $this->mTitle->getSubpageUrlForm(),
                        'basepagename' => wfEscapeWikiText( $this->mTitle->getBaseText() ),
                        'basepagenamee' => wfUrlEncode( str_replace( ' ', '_', $this->mTitle->getBaseText() ) ),
                        'revisionid' => $this->mRevisionId,
                        'revisionday' => intval( substr( $this->getRevisionTimestamp(), 6, 2 ) ),
                        'revisionday2' => substr( $this->getRevisionTimestamp(), 6, 2 ),
                        'revisionmonth' => substr( $this->getRevisionTimestamp(), 4, 2 ),
                        'revisionmonth1' => intval( substr( $this->getRevisionTimestamp(), 4, 2 ) ),
                        'revisionyear' => substr( $this->getRevisionTimestamp(), 0, 4 ),
                        'revisiontimestamp' => $this->getRevisionTimestamp(),
                        'namespace' => str_replace('_',' ',$wgContLang->getNsText( $this->mTitle->getNamespace() ) ),
                        'namespacee' => wfUrlencode( $wgContLang->getNsText( $this->mTitle->getNamespace() ) ),
                        'talkspace' => $this->mTitle->canTalk() ? str_replace('_',' ',$this->mTitle->getTalkNsText()) : '',
                        'talkspacee' => $this->mTitle->canTalk() ? wfUrlencode( $this->mTitle->getTalkNsText() ) : '',
                        'subjectspace' => $this->mTitle->getSubjectNsText(),
                        'subjectspacee' =>wfUrlencode( $this->mTitle->getSubjectNsText() ),
                        'currentdayname' => $wgContLang->getWeekdayName( gmdate( 'w', $ts ) + 1 ),
                        'currentyear' => $wgContLang->formatNum( gmdate( 'Y', $ts ), true ),
                        'currenttime' => $wgContLang->time( wfTimestamp( TS_MW, $ts ), false, false ),
                        'currenthour' => $wgContLang->formatNum( gmdate( 'H', $ts ), true ),
                        'currentweek'=> $wgContLang->formatNum( (int)gmdate( 'W', $ts ) ),
                        'currentdow'=>$wgContLang->formatNum( gmdate( 'w', $ts ) ),
                        'localdayname'=> $wgContLang->getWeekdayName( $localDayOfWeek + 1 ),
                        'localyear' => $wgContLang->formatNum( $localYear, true ),
                        'localtime' => $wgContLang->time( $localTimestamp, false, false ),
                        'localhour' => $wgContLang->formatNum( $localHour, true ),
                        'localweek' => $wgContLang->formatNum( (int)$localWeek ),
                        'localdow' =>$wgContLang->formatNum( $localDayOfWeek ),
                        'numberofarticles' => $wgContLang->formatNum( SiteStats::articles() ),
                        'numberoffiles' =>$wgContLang->formatNum( SiteStats::images() ),
                        'numberofusers' => $wgContLang->formatNum( SiteStats::users() ),
                        'numberofpages' => $wgContLang->formatNum( SiteStats::pages() ),
                        'numberofadmins' => $wgContLang->formatNum( SiteStats::numberingroup('sysop') ),
                        'numberofedits' =>$wgContLang->formatNum( SiteStats::edits() ),
                        'currenttimestamp' => wfTimestampNow(),
                        'localtimestamp' => $localTimestamp,
                        'currentversion' => SpecialVersion::getVersion(),
                        'sitename' =>$wgSitename,
                        'server' => $wgServer,
                        'servername' => $wgServerName,
                        'scriptpath' =>$wgScriptPath,
                        'directionmark' => $wgContLang->getDirMark(),
                        'contentlanguage' => $wgContLanguageCode,
                        'pageid' => $this->mTitle->getArticleID(),
                        'namespacenumber' => $this->mTitle->getNamespace(),
                        'numberofactiveusers' => $wgContLang->formatNum( SiteStats::activeUsers() ),
                        'revisionuser' => $this->mRevision->getUserText(),
                        'stylepath' => $wgStylePath
                );
                
                // These are a bit more complicated...
                //case 'talkpagename':
                if( $this->mTitle->canTalk() ) {
                        $talkPage = $this->mTitle->getTalkPage();
                        $talkpagename =  wfEscapeWikiText( $talkPage->getPrefixedText() );
                } else {
                        $talkpagename =  '';
                }
                $w2lVars['talkpagename'] = $talkpagename;

                //case 'talkpagenamee':
                if( $this->mTitle->canTalk() ) {
                        $talkPage = $this->mTitle->getTalkPage();
                        $talkpagenamee =  $talkPage->getPrefixedUrl();
                } else {
                        $talkpagenamee =  '';
                }
                $w2lVars['talkpagenamee'] = $talkpagenamee;

                //case 'subjectpagename':
                $subjPage = $this->mTitle->getSubjectPage();
                $w2lVars['subjectpagename'] =  wfEscapeWikiText( $subjPage->getPrefixedText() );
                $w2lVars['subjectpagenamee'] = $subjPage->getPrefixedUrl();
                
                wfRunHooks('w2lTemplateVars', array(&$this, &$w2lVars) );
                
                $w2lVars = array_change_key_case($w2lVars, CASE_UPPER);
                
                return $w2lVars;
        }

	function getRevisionTimestamp() {
		// At this point, we're dealing only with current articles.
		// Needs to be changed, if oldid support is integrated!!!
		// Required for prepareVariables
		return $this->mArticle->getTimestamp();
	}

	public function createTemplateFiles($template) {
		// extract all the files, which are in it
		// AND: All the files, that need to be stripped out of other pages...
		// required latexfiles:
		$other_files = $this->getTagContents($template, 'latexpage');

		$template = ' '.$template;
		$template_sec = explode('<latexfile name="', $template);

		array_shift($template_sec);
		$files = array();

		// die anderen Dateien
		foreach ($other_files as $file) {
			$file_content = $this->getWiki($file);
			$tmp_content = $this->getTagContents($file_content, "latex");
			$file_name = str_replace("LaTeX:", "", $file);
			$file_name .= ".tex";
			$files[$file_name] = trim($tmp_content[0]);
		}

		// Die Dateien aus dem Template
		foreach ($template_sec as $file_info) {
			$file = explode('">', $file_info,2);
			$file_name = $file[0].".tex";

			$file_content = explode('</latexfile>', $file[1],2);
			$files[$file_name] = trim($file_content[0]);
		}

		return $files;
	}

	public function getParserConfig() {
		$config = array();
		$this->addParserParameter('babel');
		$this->addParserParameter('documentclass');
		$this->addParserParameter('process_curly_braces');
		$this->addParserParameter('template');
		
		wfRunHooks('w2lRegisterOptions', array(&$this));

		foreach($this->parserParams as $key) {
			$config[$key] = $this->mValues->getVal($key);
		}		

		return $config;
	}

	public function addParserParameter($urlname) {
		$this->parserParams[] = $urlname;
		return true;
	}

	public function getTagContents($string, $tag) {
		$string = " ".$string;
		$open_tag = "<".$tag.">";
		$close_tag = "</".$tag.">";
		$contents = explode($open_tag, $string);
		$tags = array();
		array_shift($contents);

		foreach ($contents as $tag_content) {
			$tag_content2 = explode($close_tag, $tag_content,2);
			$tags[] = $tag_content2[0];
		}
		return $tags;
	}

        private function getStatusMessage($msg, $success = true) {
                // Show a message, if form os called by a redirect from deleting all temp-files
                $output = '';
                if ( $success == true ) {
                        $output = "\n".'<div onclick="this.style.display=\'none\'" style="text-align:center;border:1px solid black; background-color:#3c6; padding:5px; margin:5px;">';
                        $output .= $msg;
                        $output .= '</div>';
                } elseif ( $success == false ) {
                        $output = '<div onclick="this.style.display=\'none\'" style="text-align:center;border:1px solid black; background-color:#c33; padding:5px; margin:5px;">';
                        $output .=  $msg ;
                        $output .= '</div>';
                } else {
                        $output = '<div onclick="this.style.display=\'none\'" style="text-align:center;border:1px solid black; background-color:'.$success.'; padding:5px; margin:5px;">';
                        $output .= $msg;
                        $output .= '</div>';
                }
                return $output;
        }

	public function getTemplate($id) {
		$title = Title::newFromID( $id );
		if ( !$title->UserCanRead() ) {
			return '';
		}
		$rev  = new Article( $title, 0 );
		$text  = $rev->getContent();
		return $text;
	}

	public function getWiki($title_str) {
		$title = Title::newFromText( $title_str );
		if ( !$title->UserCanRead() ) {
			return '';
		}
		$rev   = new Article( $title, 0 );
		$text  = $rev->getContent();
		return $text;
	}

	public function getFolderLinks() {
		$output = '<div style="text-align:right;">';

		//$output .= '<a href="'.$this->getF('tmp', 'url-rel').'">(temporary files)</a> ';
		$output .= '<a href="'.$this->mTitle->getLocalUrl( 'action=w2lcleartempfolder' ).'" onclick="return confirm(\''.wfMsg('w2l_delete_confirmation').'\');">('.wfMsg('w2l_form_delete_link').')</a>';
		$output .= '</div>';
		return $output;
	}

	public function getTempDirPiece() {
		return $piece = time()."-".rand();
	}

	public function getTitle() {
		return $this->mTitle->getEscapedText();
	}
	
	public function getVal($key) {
		if ( array_key_exists($key, $this->options) ) {
			return $this->options[$key];
		} else {
			return W2L_UNDEFINED;
		}
	}

}

