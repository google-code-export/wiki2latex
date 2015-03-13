## Wiki2LaTeX 13 (released 2013-02-13) ##
  * Fixed not escaping circumflex character
  * Fixed a php notice
  * Fixed linking to a media page of Mediawiki
  * Fixed an error regarding user settings
  * Hopefully reduced memory usage
  * Fixed an incompatibility with MW 1.18
  * Choosing a template which is only a redirect page resulted in an error. Now redirects are not shown any more.
  * Added support for Behavior switches (on page DIRECTIVES).
  * Added a mathjax-contrib-file (experimental atm)
  * Added some new Magic Words
  * (Hopefully) Fixed some bugs in table code
  * Added new hooks
  * Added a way to optionally remove indentation of tables

## Aegaeon: 2011-02-22 ##
  * Added div and span transformation. Works with CSS classes. Style attributes are not recognized. See spandiv.txt in doc folder for more information.
  * Added user setting for Mathpazo (Accessible via "Wiki2LaTeX"-Tab in your Mediawiki-Settings)
  * Added a user setting to enable microtype by default
  * Added a user setting for your preferred babel language
  * Added a user setting for debug mode
  * Moved (show log file) in W2L-HTML form to a user setting
  * Added a user setting to show parsed LaTeX-code on W2L result page
  * Fixed some inconsistencies on various result pages
  * Added a new debug message system to parser. Use it by calling: PARSER->debugMessage('Caller', 'your message'). This makes debugging W2L and its extensions easier. Messages are only displayed in debug mode.
  * Added disableCache method to parser. Doesn't do anything, but is required for a more general extension support
  * Fixed a bug, which caused some extra verbatim environments under certain circumstances.
  * Avoid empty verbatim environments
  * New versioning scheme: Each W2L version is now called after a Saturn moon and its release date.

# v.0.11 #

  * Removed ((WikiContent)) in LaTeX-Templates. Replace it with: ((W2L\_CONTENT))
  * Removed support for old config-arrays $w2l\_config and others. This only affects very old installations. Change them in your config files as such:
  * $w2l\_config     to $w2lConfig;
  * $w2l\_tags       to $w2lTags;
  * $w2l\_pFunctions to $w2lParserFunctions;
  * Removed reference-support from w2lDefaultConfig.php. Reactivate it by adding include('contrib/ref.php'); to your own w2lConfig.php
  * Changed export options from radio buttons to clickable buttons. Removed `$w2lConfig['default_action']` which is useless now.
  * Fixed an imageparsing bug
  * Fixed a bug, which accidentally triggered table parsing where no table is.
  * Added indented `<math>` support: "`:<math>...`" now becomes "`\begin{equation}...`" (thx to cjiahao)
  * Added German Translation
  * Added Polish translation (thx to: Szymon Tarnowski)
  * Added $frame to tag-definitions (which is submitted by MW 1.16). Has no meaning at this point in W2L. This change (should not but) might break any tag extension. In case of errors please check, if the function definition matches the one given in the docs.
  * W2L now parses plain links in an article.
  * Added an empty template. You can use this if the page contains a complete LaTeX-document
  * LaTeX compile now repeats as often as you specify in `$w2lConfig['ltx_repeat']`. Previously it missed the last run.
  * W2L is now compatible with the new (and shiny) Vector theme.

# v.0.10 RC1 and RC2 #
  * Several bugs have been fixed
  * Removed some custom Template-Variables (like ((Title)) ). Please use the corresponding Mediawiki-variables in your templates, in this case ((PAGENAME)).
  * Wiki2LaTeX now uses a different Message-system. This is tested only on Mediawiki 1.16.


# v.0.9.2 #
  * Most Tables can now be rendered without using the latexfmt-attribute. (Thanks to Hendrik)
  * Fixed image rendering.
  * Fixed .tex-file download. (Thanks to felixjmoeller)

# v.0.9.1 #
  * Fixed one error message.
  * Fixed one php-warning.

# v.0.9 #
  * Performance improvements by not loading two classes when they are not needed.
  * added support for definition lists and by that also for discussion threads formatted with ':'.
  * New hooks to work with.
  * added most HTML-Entities representing greek letters
  * Renamed some config-values:
  * <tt>$w2lConfig['command']</tt> has been renamed to <tt>$w2lConfig['ltx_command']</tt>
  * <tt>$w2lConfig['repeat']</tt> has been renamed to <tt>$w2lConfig['ltx_repeat']</tt>
  * added a possibility to clear the tempfolder everytime the latexform is shown
  * Now using system-temp-directory
  * Cleaning up temp-folder no longer removes other files than those created by Wiki2LaTeX
  * Code cleaning and removed no-longer-needed configvalues
  * fixes some smaller bugs
  * fixed some links-related bugs
  * fixed an issue regarding Internet Explorer
  * fixes an incompatibility with postgresql-based Wikis.
  * Moved LaTeX-CLI into an own class

# v.0.8 #
  * Removed pdf-archive. It will be back in a future version but will be using Mediawikis upload-feature.
  * Removed changing noinclude/includeonly behaviour.
  * Removed typographic-quote-detection (too buggy to be useful)
  * Removed Paralist-support. It is now available as contribution.
  * Removed GeSHi-integration.
  * Added a contrib-directory where some Presets for Mediawiki-Extensions and LaTeX-Packages can be found. Further information can be found in the [[Extension:Wiki2LaTeX/Documentation/Contributions|documentation]]. Pre, math, ref and references are activated by default.
  * Added some more descriptive error messages.
  * Internal Links can now optionally link to the originating article on the wiki (by adding <tt>include('contrib/linkify.php');</tt> to w2lConfig.php).
  * Added support for &lt;strong&gt; and &lt;em&gt; and &lt;br/&gt; HTML-Tags
  * Greatly improved Auto-Template-Feature as it is now quite extensible.
  * Now every template is required to use babel (english users might not need it).
  * Quotes should now work fine. They are specific to the selection of babel-language.
  * Now using Mediawikis Hook-System. This change breaks every old function which made use of the eventsystem of older versions. They have to be changed. Please have a look into <tt>examples/event_function.txt</tt> and the documentation of [[Extension:Wiki2LaTeX/Documentation/Hooks|all hooks provided]].
  * Fixed some Warnings and Notices thrown by PHP
  * Wiki2LaTeX can now be used without a w2lConfig.php-file.
  * By changing the default LaTeX-command, Wiki2LaTeX should no longer crash when packages are missing or other LaTeX-errors occur.
  * Made some improvements to the result screen.
# v.0.7 #
'''Before updating please check out [The update instructions](http://code.google.com/p/wiki2latex/wiki/Update) as several changes are required to update an existing W2L installation.'''
  * [feature](feature.md) Improved table support:
  * [feature](feature.md) Support colspan-attribute (rowspan is ''not'' supported)
  * [feature](feature.md) |---- as row-delimiter gives a double line in latex
  * [feature](feature.md) Add latexwidth attribute
  * [feature](feature.md) Support for nested tables (a bit ugly though) was added
  * [feature](feature.md) Added a pdf-archive
  * [feature](feature.md) Added the ability to automagically create a LaTeX-Template, thus removing the option to use no template when exporting an article to pdf.
  * [feature](feature.md) Wiki2LaTeX is now localizable.
  * [feature](feature.md) Added limited html-tag support
  * [feature](feature.md) Temporary files can now be deleted (there is a link next to the start export-button).
  * [feature](feature.md) tmp and archive path can be put anywhere, as long as they reside somewhere below the Mediawiki-root.
  * [fixed](fixed.md) Some LINUX-related fixes
  * [fixed](fixed.md) Add some space to the latex-tab so tabs look grouped.
  * [change](change.md) Changed names of configuration arrays. The old names can still be used.
  * [change](change.md) Changed installation sequence
  * [change](change.md) Now checking for required Mediawiki-Version, as older versions are not ''tested''.
  * [change](change.md) Removed some config-flags.
  * [intern](intern.md) Completely reorganized code
  * [intern](intern.md) Code that deals with directories has been rewritten.

# v.0.6.2 #
  * [fixed](fixed.md) Quotation marks did not work
  * [fixed](fixed.md) Typographic quote detection does not work
  * [fixed](fixed.md) Check for an unexpected value when generating Template-Variables
  * [fixed](fixed.md) (Hopefully) fixed an issue which prevented templates from loading on non-german installations of Mediawiki.

# v.0.6.1 #
  * [fixed](fixed.md) HTML-Entities did not work

# v.0.6 #
  * [feature](feature.md) Tables are supported now (requires tabularx package!) Please read [[talk:Wiki2LaTeX/Development/w2lParser.php](Extension.md)] for further information. (Thanks to [[User:OleDahle|Ole Dahle]])
  * [feature](feature.md) Images are supported (graphicx-package required!) (Thanks to [[User:OleDahle|Ole Dahle]])
  * [feature](feature.md) Variables like <tt>

<nowiki>

{{SITENAME}}<br>
<br>
</nowiki><br>
<br>
</tt> are supported.
  * [feature](feature.md) These variables can also be inserted into LaTeX-Templates by using <tt>((SITENAME))</tt> (Variablename in double normal braces).
**[fixed](fixed.md) Compatible with Mediawiki 1.11**

# v.0.5 #
  * [feature](feature.md) Nested templates and parserfunctions are supported now
  * [feature](feature.md) Documentclass can be selected, so headings are generated properly
  * [feature](feature.md) functions that are registered to some (w2l)-hook can now be disabled
  * [feature](feature.md) filetype of putput can be customized, so ps and dvi output is possible
  * [feature](feature.md) amount of repetitions of calling LaTeX-command can now be customized so enabling MikTeXs 'texify.exe', which runs LaTeX as often as neccessary
  * [feature](feature.md) default action and default template can be set in configfile
  * [fixed](fixed.md) crash when LaTeX-namespace is not created
  * [fixed](fixed.md) crash when exporting to pdf with no LaTeX-Template selected
  * [fixed](fixed.md) geshi can now be disabled by a configflag
  * [fixed](fixed.md) pathinformation is not required for calling the latexcommand
  * [fixed](fixed.md) Better use of Mediawikis internal ways to request content of articles
  * [fixed](fixed.md) <tt>

<nowiki>

<br>
<br>
<includeonly><br>
<br>
<br>
<br>
</includeonly><br>
<br>
<br>
<br>
</nowiki><br>
<br>
<tt> sections were not parsed correctly, though complex noinclude-includeonly structures might still fail.<br>
<ul><li><a href='fixed.md'>fixed</a> Support for the parserfunction extension was removed from default settings. To reenable it, copy some commented lines of the w2lConfig.sample.php over to your w2lConfig.php.<br>
</li><li><a href='fixed.md'>fixed</a> personal config file is not overwritten when updating the extension<br>
</li><li><a href='fixed.md'>fixed</a> '?>' closing tag removed from all files (in line with Mediawiki 1.11)