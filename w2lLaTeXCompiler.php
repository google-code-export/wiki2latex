<?php

/*
 * File: w2lLaTeXCompiler.php
 *
 * Purpose:
 * Provides the cli-interface to LaTeX
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

class Wiki2LaTeXCompiler {

	function __construct($piece, $mkdir = true) {
		global $w2lConfig;
		$this->files   = array();
		$this->command = $w2lConfig['ltx_command'];
		$this->sort    = $w2lConfig['ltx_sort'];
		$this->bibtex  = $w2lConfig['ltx_bibtex'];
		$this->repeat  = $w2lConfig['ltx_repeat'];

		$this->piece   = $piece;
		$this->path    = '';
		$this->mkdir   = $mkdir;
		
		$this->debug   = false;
		$this->log     = '';
		$this->timer   = 0;
		return true;
	}
	
	function addFiles($files) {
		$this->files = $files;
	}
	
	function generateFiles($tpl_vars) {
		global $wgOut, $wgUser;
		
		$this->debug = $wgUser->getOption('w2lDebug');
		$this->is_admin = in_array('sysop', $wgUser->getGroups());
		
		$msg = '';
		$tempdir  = w2lTempDir();
		
		if ( $this->debug == true && $this->is_admin == true) {
			$msg .= "System temp dir: ".$tempdir."\n";
		}
		
		if ( substr($tempdir, -1) != DIRECTORY_SEPARATOR ) {
			$tempdir .= DIRECTORY_SEPARATOR;
		}
		
		$tempdir .= 'w2ltmp-'.$this->piece;
		
		if ( $this->debug == true && $this->is_admin == true) {
			$msg .= "W2L temp dir: ".$tempdir."\n";
		}
		
		$this->path = $tempdir;
		

		if ( true == $this->mkdir ) {
			if ( !@mkdir($this->path) ) {
				wfVarDump($this->path);
				$wgOut->addHTML( wfMsg('w2l_temp_dir_missing') );
				$this->msg = $msg;
				return false;
            }
        
            $chmod = chmod($this->path, 0777);
            
			if ( !file_exists($this->path) OR !is_dir($this->path) OR !is_writable($this->path) ) {
				$wgOut->addHTML( wfMsg('w2l_temp_dir_missing') );
				$this->msg = $msg;
				return false;
			}
			

		}

		$cur_dir = getcwd();
		chdir($tempdir);

		foreach ( $this->files as $file_name => $file_content) {
			$file_content = str_replace(array_keys($tpl_vars), array_values($tpl_vars), $file_content);
			$failure = file_put_contents($file_name, $file_content);
			if ( $failure !== false ) {
				$msg .= 'Creating file '.$file_name.' with '.$failure.' Bytes'."\n";
			} else {
				$msg .= 'Error creating file '.$file_name."\n";
			}

			chmod($file_name, 0666);
		}
		chdir($cur_dir);
		$this->msg = $msg;
		return true;
	}
	
	function runLaTeX( $file = 'Main', $sort = false, $bibtex = false ) {
		
		global $wgUser;
		$timer_start = microtime(true);
		$this->debug = $wgUser->getOption('w2lDebug');
		$this->is_admin = in_array('sysop', $wgUser->getGroups());
		
		$command = str_replace('%file%', $file, $this->command);

		$cur_dir = getcwd();
		chdir($this->path);
	
		$go  = true;
		$i   = 1;
		$msg = $this->msg;
		
		if ( $this->debug == true && $this->is_admin == true) {
			$msg .= "\n== Debug Information ==\n";
			$msg .= wfMsg('w2l_compile_command', $command )."\n";
			$msg .= wfMsg('w2l_temppath', $this->path )."\n";
			$msg .= "Current directory: ".getcwd()."\n";
			$msg .= "User: ".wfShellExec("whoami")."\n";
			$msg .= "== PDF-LaTeX Information ==\n".wfShellExec("pdflatex -version");
		}
		
		while ( (true == $go ) OR ( $i > 5 ) ) {
			$msg .= "\n".wfMsg('w2l_compile_run', $i)."\n";

			$msg .= wfShellExec($command);

			if ( !file_exists( $file.'.pdf' ) ) {
				$msg .= wfMsg('w2l_pdf_not_created', $file.'.pdf')."\n";
				$msg .= "Current directory: ".getcwd()."\n";
				$msg .= "Is it writable? ".wfBoolToStr(is_writable(getcwd()))."\n";
				$msg .= "Is it a font-problem maybe? ".wfShellExec('kpsewhich -var-value TFMFONTS')."\n";
				$msg .= "Is it a path maybe? ".wfShellExec('kpsewhich -var-value HOME')."\n";
				$compile_error = true;
				$go = false;
			} else {
				$compile_error = false;
				/*
				if ( true == $sort ) {
					// sort it, baby
					$msg .= '===Sort-Result==='."\n";
					$msg .= $this->sortIndexFile($file);
					$msg .= "\n";
				}
				
				if ( true == $bibtex ) {
					// run bibtex
					$msg .= '===BibTeX-Result==='."\n";
					$msg .= $this->doBibTex($file);
					$msg .= "\n";
				}
				*/
				
				if ( $this->repeat == $i ) {
					$go = false;
				}
				
				++$i;

			}
		}

		// Now chmod-ing some files
		$mod = 0666;
		if ( is_dir($this->path) ) {
			$directory = dir($this->path);
			while ( false !== ($tmp_file = $directory->read()) ) {
				if ( is_file($tmp_file) ) {
					$res = chmod($tmp_file, $mod);
				}
			}
			$directory->close();
		}
		if ( file_exists($file.'.log') ) {
			$this->log = file_get_contents($file.'.log');
		} else {
			$this->log = 'Log file was not created...';
		}
		chdir($cur_dir);
		$timer_end = microtime(true);
		$this->timer = $timer_end - $timer_start;
		$this->timer = round($this->timer, 3);
		$msg .= "Running time LaTeX: ".$timer." seconds";
		$this->msg = $msg;
		return $compile_error;
	}
	
	function sortIndexFile($file = 'Main') {
		$command = str_replace('%file%', $file, $this->sort);
		$msg  = 'Command: '.$command; 
		$msg .= wfShellExec($command);
		return $msg;
	}
	
	function doBibTex($file = 'Main') {
		// Disabling this for now.
		return 'Bibtex is unsupported right now.';
		
		$command = str_replace('%file%', $file, $this->bibtex);
		$msg  = 'Command: '.$command; 
		$msg .= wfShellExec($command);
		return $msg;
	}
	
	function getLog() {
		return $this->getCompileLog();
	}
	function getCompileLog() {
		return $this->msg;
	}
	
	function getLogFile() {
		return $this->log;
	}
	
	function getDebugInformation() {
		$msg = '';
		
		$pdflatex_version = wfShellExec("pdflatex -version");
		
	}
}

