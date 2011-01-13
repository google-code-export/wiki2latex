<?php

/*
 * File: w2lContentCatcher.php
 *
 * Purpose:
 * Fetches Content from DB or URLs
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

class Wiki2LaTeXContentCatcher {
	function __construct() {
	
	}
	
	function getTextByTitle( $title_str ) {
		$title = $this->getTitleObject('text', $title_str);
		if ( $title == '' ) {
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
	
	function getTitleObject( $type = 'text', $title_data, $namespace = NS_MAIN ) {
		switch ($type) {
			case 'text':
				$title_data = trim($title_data);
				$title = Title::newFromText( $title_data, $namespace );
			break;
			case 'id':
				$title = Title::newFromID( $title_data );
			break;
		}
		
		if ( !is_a( $title, 'Title' ) ) {
			return $title_data;
		}
		
		if ( !$title->UserCanRead() ) {
			return '';
		}
		return $title;
	}
	
	function getTextById($id) {
		$title = $this->getTitleObject('id', $id);
		if ( $title == '' ) {
			return '';
		}
		$rev  = new Article( $title, 0 );
		$text  = $rev->getContent();
		return $text;
	}
}

