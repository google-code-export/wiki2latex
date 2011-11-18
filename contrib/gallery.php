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
 
if ( !function_exists('w2lGallery') ) {

	$w2lTags['source'] = 'w2lGallery';

	function w2lGallery($input, $argv, $parser, $frame = false, $mode = 'latex') {

		// $input should be a list of Images like this:
		// http://www.mediawiki.org/wiki/Help:Images#Rendering_a_gallery_of_images
		$gallery = "";
		$caption = '';
		$parser->addPackageDependency('graphicx');
		
		$parts = explode("\n", $input);
		
		foreach ($parts as $part ) {
			// check for caption...
			$data = explode("|", $part, 2);
			
			if ( isset($data[1]) ) {
				$caption = $data[1];
			}
			
			// get filepath (as is used in w2lParser.php)
				$title = Title::makeTitleSafe( NS_IMAGE, $data[0] );
				$file = Image::newFromTitle( $title );
				$file->loadFromFile();
				
				if ( $file && $file->exists() ) {
					$imagepath = $file->getPath();
					$imagepath = str_replace('\\', '/', $imagepath);
					$title = $file->getTitle()->getText();
					$gallery .= "\\begin{minipage}[t]{4cm}\n\includegraphics{{$imagepath}}}\\\\ \\textit{{$caption}}\n\end{minipage}\n";
				
				} else {
					// does not exist!!!
					$case_imagename = str_replace('_', ' ', $case_imagename);
					$gallery .= "\\begin{minipage}[t]{4cm}\n$case_imagename\n\end{minipage}\n";
				}

			
			// make LaTeX-Code					
			$masked_command = $parser->getMark("gallery");
			$parser->mask($masked_command, $gallery);
		}
		
		return $gallery;
	}
}
