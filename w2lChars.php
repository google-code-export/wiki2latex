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

// $this->addChar('HTMLNAME', 'LATEXCOMAND' , UNICODE_DEC_NUMBER);

$this->addChar( '&larr;', '\(\leftarrow{}\)', 8592);
$this->addChar( '&uarr;', '\(\uparrow\)', 8593);
$this->addChar( '&rarr;', '\(\rightarrow{}\)', 8594);
$this->addChar( '&darr;', '\(\downarrow{}\)', 8595);
$this->addChar( '&harr;', '\(\leftrightarrow{}\)', 8596);
$this->addChar( '&lArr;', '\(\Leftarrow{}\)', 8656);
$this->addChar( '&uArr;', '\(\Uparrow\)', 8657);
$this->addChar( '&rArr;', '\(\Rightarrow{}\)', 8658);
$this->addChar( '&dArr;', '\(\Downarrow{}\)', 8659);
$this->addChar( '&hArr;', '\(\Leftrightarrow{}\)', 8660);

/* Currencysymbols */
$this->addChar( '&euro;', '{\euro}', 8364, 'eurosym');
$this->addChar( '&pound;', '{\pounds}', 163);


/* Punctation */
$this->addChar( '&hellip;', '{\dots}', 8230);

/* Some signs */
$this->addChar( '&trade;', '{\texttrademark}', 8482);
$this->addChar( '&nbsp;', '~', 160);
$this->addChar( '&amp;', '\&', 38);
$this->addChar( '&sect;', '{\S}', 167);
$this->addChar( '&copy;', '{\copyright}', 169);
$this->addChar( '&reg;', '{\textregistered}', 174);

$this->addChar( '&ccedil;', '\c{c}', 231);
$this->addChar( '&asymp;', '\(\approx{}\)', 8776);
$this->addChar( '&ne;', '\(\neq{}\)', 8800);

$this->addChar('&ndash;', '--', 8211);
$this->addChar('&mdash;', '---', 8212);
$this->addChar('&lt;', '<', 60);
$this->addChar('&gt;', '>', 62);

// greek 
$this->addChar('&Alpha;', '\(\Alpha{}\)' , 913 );
$this->addChar('&alpha;', '\(\alpha{}\)' , 945);
$this->addChar('&Beta;', '\(\Beta{}\)' , 914);
$this->addChar('&beta;', '\(\beta{}\)' , 946 );
$this->addChar('&Gamma;', '\(\Gamma{}\)' , 915);
$this->addChar('&gamma;', '\(\gamma{}\)' , 947);
$this->addChar('&Delta;', '\(\Delta{}\)' , 916);
$this->addChar('&delta;', '\(\delta{}\)' , 948);
$this->addChar('&Epsilon;', '\(\Epsilon{}\)' , 917);
$this->addChar('&epsilon;', '\(\epsilon{}\)' , 949);
$this->addChar('&Zeta;', '\(\Zeta{}\)' , 918);
$this->addChar('&zeta;', '\(\zeta{}\)' , 950);
$this->addChar('&Eta;', '\(\Eta{}\)' , 919);
$this->addChar('&eta;', '\(\eta{}\)' , 951);
$this->addChar('&Theta;', '\(\Theta{}\)', 920 );
$this->addChar('&theta;', '\(\theta{}\)' , 952);
$this->addChar('&Iota;', '\(\Iota{}\)', 921);
$this->addChar('&iota;', '\(\iota{}\)', 953);
$this->addChar('&Kappa;', '\(\Kappa{}\)', 922);
$this->addChar('&kappa;', '\(\kappa{}\)', 954);
$this->addChar('&Lambda;', '\(\Lambda{}\)', 923);
$this->addChar('&lambda;', '\(\lambda{}\)', 955);
$this->addChar('&Mu;', '\(\Mu{}\)', 924);
$this->addChar('&mu;', '\(\mu{}\)', 956);
$this->addChar('&Nu;', '\(\Nu{}\)', 925);
$this->addChar('&nu;', '\(\nu{}\)', 957);
$this->addChar('&Xi;', '\(\Xi{}\)', 926);
$this->addChar('&xi;', '\(\xi{}\)', 958);
$this->addChar('&Omicron;', '\(\Omicron{}\)', 927);
$this->addChar('&omicron;', '\(\omicron{}\)', 959);
$this->addChar('&Pi;', '\(\Pi{}\)', 928);
$this->addChar('&pi;', '\(\pi{}\)', 960);
$this->addChar('&Rho;', '\(\Rho{}\)', 929);
$this->addChar('&rho;', '\(\rho{}\)', 961);
$this->addChar('&Sigma;', '\(\Sigma{}\)', 931);
$this->addChar('&sigma;', '\(\sigma{}\)', 963);
$this->addChar('&Tau;', '\(\Tau{}\)', 932);
$this->addChar('&tau;', '\(\tau{}\)', 964);
$this->addChar('&Upsilon;', '\(\Upsilon{}\)', 933);
$this->addChar('&upsilon;', '\(\upsilon{}\)', 965);
$this->addChar('&Phi;', '\(\Phi{}\)', 934);
$this->addChar('&phi;', '\(\phi{}\)', 966);
$this->addChar('&Chi;', '\(\Chi{}\)', 935);
$this->addChar('&chi;', '\(\chi{}\)', 967);
$this->addChar('&Psi;', '\(\Psi{}\)', 936);
$this->addChar('&psi;', '\(\psi{}\)', 968);
$this->addChar('&Omega;', '\(\Omega{}\)', 937);
$this->addChar('&omega;', '\(\omega{}\)', 969);



