# Which issues are covered? #

This wiki page covers problemes, should W2L fail to generate a pdf. This can be caused by a variety of issues.

# Cause #1: The right LaTeX command #

By default, W2L uses the plain and simple command `pdflatex Main` to compile its LaTeX-file. While this commonly works, there are several reports of issues with it. Some of these commands use the placeholder %file%.

  * It might help to explicitly call pdflatex with its path, i.e. `/usr/bin/pdflatex  -interaction=nonstopmode %file%`
  * There are reports suggesting, that you need to include a HOME-variable. Details can be read in this issue-report: http://code.google.com/p/wiki2latex/issues/detail?id=68
  * Some webservers might be able to set some environment-vars themself by a setting more or less called "inherit environment".

Basicly you should try these different LaTeX calls. If none of these alternatives work, you should definitely report an issue here.

# Cause #2: Errors in your LaTeX-code #

  * If your LaTeX-code calls LaTeX-packages which are not installed on your system/server it might fail. Please check, if all packages are installed. In order to test this, you can simply run `pdflatex Main` in the corresponding temp directory. Install missing LaTeX packages if neccessary.
  * There might be bugs in W2L which creates a tex-doc, which contains syntax errors. Most commonly there are two causes: Either your Mediawiki is containing errors which are not corrected by W2L or you are using very complex template constructions or tables. If in doubt please report an issue report here.

# Cause #3: Issues regarding your temp directory #

LaTeX needs to create files. W2L therefore creates a temp directory for every attempt to create a pdf file. This directory needs to be writable by the webserver. W2L should do a pretty good job detecting these errors and inform you. Still, sometimes an error slips in this regard.