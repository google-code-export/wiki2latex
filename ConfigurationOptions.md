# User Preferences #
User preferences reside in the preferences of Mediawiki.

# Administrative Settings #

Administrative settings are saved in <tt>w2lConfig.php</tt>. This file is optional as Wiki2LaTeX is configured in a way that should fit most needs.

All config flags are saved in <tt>w2lDefaultConfig.php</tt>. Please don't edit this file as it might change with every new version.

## Important Settings ##

  * `$w2lConfig['ltx_command']`: This command is executed to compile tex files to pdf.
  * `$w2lConfig['magic_template']`: This php file holds a Template which is used, when (magic)-template is selected.
  * `$w2lConfig['allow_anonymous']`: Are anonymous users allowed to view Wiki2LaTeX? Caution: This also allows anonymous users to start a pdf-compile.
  * `$w2lConfig['pdfexport']`: You can enable or disable pdf export. Setting this to false allows only the creation of tex files.