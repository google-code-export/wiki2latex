<?php

$wgHooks['w2lFormFieldsets'][]               = 'w2lCompatibilityIE8';

function w2lCompatibilityIE8(&$parser, &$fieldsets) {

	$export_options['legend'] = wfMsg('w2l_select_output');
	$export_options['html'] = '<input type="submit" name="action" value="w2ltextarea" />'.wfMsg('w2l_select_textarea').'<br/>';
	$export_options['html'] .= '<input type="submit" name="action" value="w2ltexfiles" />'.wfMsg('w2l_select_texfiles').'<br/>';

	if ( true == $parser->config['pdfexport'] ) {
		$export_options['html'] .= '<input type="submit" name="action" value="w2lpdf" />';
		$export_options['html'] .= wfMsg('w2l_select_pdf','pdf');
		$export_options['html'] .= '<br/>';
	}
	
	$fieldsets[10] = $export_options;
	$fieldsets[1000] = str_replace('checked="checked"', '',$export_options);
	return true;
}
