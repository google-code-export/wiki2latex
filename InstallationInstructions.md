# Basic Installation #

  1. Download the current release from [Google Code](http://code.google.com/p/wiki2latex/downloads/list).
  1. Unpack your downloaded file into the folder <tt>(Mediawiki Root)/extensions/w2l/</tt>
  1. Add the following line to <tt>LocalSettings.php</tt>, near the end of the file, but before the end PHP-tag "<tt>?></tt>" (if it exists):
    * <tt>require_once($IP."/extensions/w2l/wiki2latex.php");</tt>
  1. Enjoy :)

# More Complete Installation #
## Custom Namespace for LaTeX-Templates ##

The above Installation only gives you a basic way of exporting an Article to LaTeX and PDF. If you want to customize Wiki2LaTeX's output by your own LaTeX-templates, you need to add a LaTeX-namespace:

Add this line to your LocalSettings.php:
```
$wgExtraNamespaces[100] = "LaTeX";
$wgExtraNamespaces[101] = "LaTeX_talk";
```

Note that if you already have a custom namespace, that <tt>'100'</tt> may need to be changed to an available even number <tt>'102'</tt>, <tt>'104'</tt>, etc.; Add your discussion namespace accordingly. See [Using custom namespaces](http://www.mediawiki.org/wiki/Manual:Using_custom_namespaces) on Mediawiki.org for more information.

**WARNING: Do not use 400 and 401 as your namespace numbers.**

## Custom w2lConfig.php ##
There are several circumstances when you need to use your own config file:

  * You want to use some of the contributions that allow some refinements of W2L's output.
  * You need to change the used LaTeX-command or something like it.
  * You feel the need to dive more into Wiki2LaTeX by adding Parser Functions, XML-Tag-Extensions or custom functions using hooks provided by W2L.

Simply rename <tt>w2lConfig.sample.php</tt> to <tt>w2lConfig.php</tt> and change your desired settings there. All available options are documented in <tt>w2lDefaultConfig.php</tt>. This is the place to include contributions or tell Wiki2LaTeX about your extensions.