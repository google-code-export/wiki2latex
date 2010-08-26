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

switch ( $this->getVal('babel') ) {
	case 'english':
		$this->addChar('&lsquo;', '`',  8216);
		$this->addChar('&rsquo;', "'",  8217);
		$this->addChar('&sbquo;', '`',  8218);

		$this->addChar('&ldquo;', '``', 8220);
		$this->addChar('&rdquo;', "''", 8221);
		$this->addChar('&bdquo;', '``', 8222);

		$this->addChar('&lsaquo;', '\flq{}', 8249);
		$this->addChar('&rsaquo;', '\frq{}', 8250);
		$this->addChar('&laquo;',  '\flqq{}', 171);
		$this->addChar('&raquo;',  '\frqq{}', 187);

		$this->addChar('&quot;', '"', 34);

	break;
	case 'german': // using fall-through here...
	case 'ngerman':
		$this->addChar('&lsquo;', '\glq{}',  8216);
		$this->addChar('&rsquo;', '\grq{}',  8217);
		$this->addChar('&sbquo;', '\glq{}',  8218);

		$this->addChar('&ldquo;', '"`', 8220);
		$this->addChar('&rdquo;', '"\'', 8221);
		$this->addChar('&bdquo;', '"`', 8222);

		$this->addChar('&lsaquo;', '\flq{}', 8249);
		$this->addChar('&rsaquo;', '\frq{}', 8250);
		$this->addChar('&laquo;',  '\flqq{}', 171);
		$this->addChar('&raquo;',  '\frqq{}', 187);

		$this->addChar('&quot;', '\dq{}', 34);
	break;
	case 'french':
		$this->addChar('&lsquo;', '\glq{}',  8216);
		$this->addChar('&rsquo;', '\grq{}',  8217);
		$this->addChar('&sbquo;', '\glq{}',  8218);

		$this->addChar('&ldquo;', '\glqq{}',  8220);
		$this->addChar('&rdquo;', '\grqq{}', 8221);
		$this->addChar('&bdquo;', '\glqq{}', 8222);

		$this->addChar('&lsaquo;', '\flq{}', 8249);
		$this->addChar('&rsaquo;', '\frq{}', 8250);
		$this->addChar('&laquo;',  '\og{}', 171);
		$this->addChar('&raquo;',  '\fg{}', 187);

		$this->addChar('&quot;', '\dq{}', 34);
	break;
	default: // Default is the english way...
		$this->addChar('&lsquo;', '`',  8216);
		$this->addChar('&rsquo;', "'",  8217);
		$this->addChar('&sbquo;', '`',  8218);

		$this->addChar('&ldquo;', '``', 8220);
		$this->addChar('&rdquo;', "''", 8221);
		$this->addChar('&bdquo;', '``', 8222);

		$this->addChar('&lsaquo;', '\flq{}', 8249);
		$this->addChar('&rsaquo;', '\frq{}', 8250);
		$this->addChar('&laquo;',  '\flqq{}', 171);
		$this->addChar('&raquo;',  '\frqq{}', 187);

		$this->addChar('&quot;', '"', 34);
	break;
}


