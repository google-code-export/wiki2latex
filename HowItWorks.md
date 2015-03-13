# The Basic idea #

Basicly, all W2L does, is to take the raw Mediawiki article and transform all Mediawiki-formatting into LaTeX. Why LaTeX? Simple: Mediawiki already uses LaTeX to generate math-contents. So why not transform the rest into LaTeX as well, when a way of exporting an article to PDF is required?

# Purpose of some files #

  * `wiki2latex.php`: Loads W2L into Mediawiki
  * `w2lCore.php`: Contains several functions. It calls the parser, if neccessary.
  * `w2lParser.php`: When a parsing action is requested, the parser gets called. It returns tex-files or a parsed string.
  * `w2lSendFile.php`: Sends a pdf, tex or log file to users
  * `w2lConfig.php`: This file contains some settings.


# How the parser works #

This is just a basic outline of how the W2L-parser works. You can follow this documentation within w2lParser.php: This is the file, where all the functions are declared. Especially parse() and internalParse() are quite easy to understand, as they do only call other function to do the actual work. In order to fully understand what w2L does, these two functions are a pretty good start. Here we go:

  * After the Parser has been initiated, the function parse() is called.
  * Now W2L parses Templates, nowiki-areas and xml-style extension tags.
  * The main parser-function now calls initParse(), where most of the works is done regarding Mediawikis markup syntax. W2L now transforms i.e. headings, links and tables.
  * Returning to parse() now some cleaning up is done and the parser is finished.

It is important to note, that several functions are taken from Mediawikis own parser. These functions have been modified so they return LaTeX instead of HTML.