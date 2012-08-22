=== Memento Media Shortcode Generator ===

Contributors:
Donate:
Tags: MMSG, php, css, js, stylesheet, javascript, shortcode, management
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.9r3

Shortcode Framework with advanced PHP parsing and JavaScript / CSS managing

== Description ==

Shortcode framework giving developers an easy method to use their existing
skills within WP without having to know anything about what happens in the
background of WP


**Based on:**

* <a href="http://getson.info/shortcode-generator/">Kyle Getson's Shortcode Generator</a>


**Ideas taken from WP plugins:**

* <a href="http://www.viper007bond.com/wordpress-plugins/syntaxhighlighter/">Viper007Bond's Syntax Highlighter</a>
* <a href="http://www.deanlee.cn/wordpress/google-code-prettify-for-wordpress/">Dean Lee's Google Code Prettify</a>
* <a href="http://rulesplayer.890m.com/blog/?page_id=4">bobef's WP-CodePress</a>
* <a href="http://www.q2w3.ru/2009/12/06/824/">Max Bond's Q2W3 Inc Manager</a>
* <a href="http://weston.ruter.net/projects/wordpress-plugins/">Weston Ruter's Optimize Scripts</a>
* <a href="http://www.optictheory.com/tinymce-tabfocus-patch/">John Beeler's TinyMCE Tabfocus Patch</a>
* and from all over the internet... thanx everybody! ;]


**3rd party JavaSripts attached:**

* <a href="http://codepress.sourceforge.net/">Fernando M.A.d.S.'s codepress</a>
	* added language "php_snippet", to have php code
		highlighted without the opening and closing PHP tag
	* added TAB key support, so instead of changing focus, TAB key will insert
		tabulator
* <a href="http://thechriswalker.net/">Modified version of Chris Walker's Multiple Drag&Drop Lists</a>
	* added list sort support with jQuery tinysort
	* added droptarget support
	* added Mac Cmd support

= Features =

* Shortcodes : Generate shortcodes on the fly
* Options : Create plain text, rich text, or advanced php shortcodes
* Styles : Easily manage required styles for your shortcodes (dependent and global)
* Scripts : Easily manage required scripts for your shortcodes (dependent and global)
* Rich Text Shortcode Editing : Full localized WP editor with installed addons and media library
* Advanced Shortcode Editing : Syntax Highlighted PHP editing thanks to codepress, no preprocessing needed
* Extra : Other code editors also start to use codepress (theme editor, widget editor)

== Credits ==

Copyright 2010 by Barnabás Bucsy

This file is part of MMSG.

MMSG is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

MMSG is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with MMSG. If not, see <http://www.gnu.org/licenses/>.


== Installation ==

1. Unzip
2. Upload the "assets" directory to your domains root
	* if you wish upload it elsewhere, you can, just edit "config.inc.php" in directory "Memento-SG"
3. Edit "config.inc.php" in directory "Memento-SG"
	* edit URLs corresponding to uploaded scripts and styles
3. Upload the directory "Memento-SG" to "<wordpress directory>/wp-content/plugins/"
4. Go to plugins menu in admin and activate the plugin
5. Notice new admin menus to manage shortcodes, scripts and styles

That's it ... Have fun! ;]

== Advanced Shortcode Information ==

For more information about advanced shortcodes,
see "help/_advanced_shortcode_help.php" within the package

== Screenshots ==

1. Rich Shortcode Editor
2. Advanced Shortcode Editor
3. Script Manager
4. Style Manager

== Frequently Asked Questions ==

No questions asked yet.

== Changelog ==

First release.

== Upgrade Notice ==

First release.