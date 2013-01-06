<?php
/*
 * File:    w2lMagicTemplate.php
 * Created: 2008-07-01
 * Version: 0.9
 *
 * Purpose:
 * Contains the standard Magic-Template.
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

$tmpl = <<<EOF
==Wiki2LaTeX's Magic Template==
<latexfile name="Main">
\documentclass[{$docClassOptions}]{{$docClass}}
\usepackage[{$babel}]{babel} % Quotes won't work without babel

\usepackage[T1]{fontenc}
\usepackage[utf8]{inputenc}  % This is very important!

((W2L_REQUIRED_PACKAGES))

((W2L_HEAD))

\begin{document}
((W2L_CONTENT))
\end{document}
</latexfile>
EOF;

