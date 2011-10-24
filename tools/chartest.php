<?php
define('MEDIAWIKI', true);
class chartest {
	function LoadChars() {
		include('../w2lChars.php');
		return true;
	}
	
	function addChar($html, $latex, $number = 0) {
		$this->chars[] = array('html' => $html, 'latex' => $latex, 'number' => $number);
		return true;
	}
	
	function generateTestPage() {
		$out  = 'Testing all our Characters...'."\n\n";
		$out .= '{| latexfmt="|l|l|l|l|"'."\n"."!HTML!!HTML_Entity!!LaTeX!!LaTeX-By-Numbers\n";
		foreach ($this->chars as $char)  {
			$out .= "|-\n";
			$html_com = str_replace('&', '&amp;', $char['html']);

			$out .= "|".$char['html']."||".$html_com."||<rawtex>".$char['latex']."</rawtex>||n.n.\n";
			}

		$out .= '|}'."\n";
		header('Content-type: text/plain; charset="utf-8"');
		echo $out;
		die;
	}
}

$test = new chartest;

$test->LoadChars();
$test->generateTestPage();
