<?php

/*
---------------------------------------------------------------------------

Plugin Name: Memento Media Shortcode Generator (MMSG)
Version: 0.9r3
Description: Shortcode Framework with advanced PHP parsing and JavaScript / CSS managing
Author: Barnabás Bucsy
Author URI: http://www.mementomedia.net

Based on:

-> Kyle Getson's Shortcode Generator
   http://getson.info/shortcode-generator/

Ideas taken from WP plugins:

-> Viper007Bond's Syntax Highlighter
   http://www.viper007bond.com/wordpress-plugins/syntaxhighlighter/

-> Dean Lee's Google Code Prettify
   http://www.deanlee.cn/wordpress/google-code-prettify-for-wordpress/

-> bobef's WP-CodePress
   http://rulesplayer.890m.com/blog/?page_id=4

-> Max Bond's Q2W3 Inc Manager
   http://www.q2w3.ru/2009/12/06/824/

-> Weston Rute's Optimize Scripts
   http://weston.ruter.net/projects/wordpress-plugins/

-> John Beeler's TinyMCE Tabfocus Patch
   http://www.optictheory.com/tinymce-tabfocus-patch/

-> and from all over the internet...
   thanx everybody! ;]

3rd party JavaSripts attached:

-> Fernando M.A.d.S.'s codepress
   http://codepress.sourceforge.net/
   -> added language "php_snippet", to have php code
      highlighted without the opening and closing PHP tag

---------------------------------------------------------------------------

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

---------------------------------------------------------------------------
*/

global $MMSG;
$MMSG = new MementoShortcodeGenerator();

class MementoShortcodeGenerator
{
	static $styles;
	static $shortcodes;
	static $scripts;
	static $multi_updated;

	/**
	 * CONSTRUCTOR
	 *
	 * let the world know, we exist
	 */
	function MementoShortcodeGenerator()
	{
		// MMSG settings
		define(MMSG_PLUGIN_NAME,	'Memento-SG');
		define(MMSG_VERSION,		'0.8');
		define(MMSG_DB_VERSION,		'0.96');
		define(MMSG_MIN_WP,			'2.8');
		define(MMSG_PREFIX,			'memento_');
		define(MMSG_ADMIN_URL,		get_option('siteurl') . '/wp-admin/admin.php?page=' . MMSG_PLUGIN_NAME . '/admin/');
		define(MMSG_WIDGET_CODES,	'MMSG_widget_codes');

		// localization
		load_plugin_textdomain(MMSG_PLUGIN_NAME, '/wp-content/plugins/' . MMSG_PLUGIN_NAME . '/lang/');

		// version check
		if (!$this->WP_version())
			return;

		// user settings
		require_once('config.inc.php');

		// extra special chars to be replaced in advanced shortcodes
		$this->specialchars = array(
			'\0' => '&#92;&#48;',
		);

		// register everything
		add_action('init',								array(&$this, 'MMSG_init'));

		// install / uninstall
		register_activation_hook(__FILE__,				array(&$this, 'MMSG_activation_hook'));
		register_deactivation_hook(	__FILE__,			array(&$this, 'MMSG_deactivation_hook'));

		// admin extensions
		add_filter('plugin_action_links_'.plugin_basename(__FILE__),
														array(&$this, 'MMSG_plugin_action_links'));
		add_filter('admin_head',						array(&$this, 'MMSG_admin_head'));
		add_action('admin_menu',						array(&$this, 'MMSG_admin_menu'));
		add_action('admin_footer',						array(&$this, 'MMSG_admin_footer'));
		add_filter('the_content',						array(&$this, 'MMSG_the_content'), 				7);
		add_action('the_posts',							array(&$this, 'MMSG_the_posts'));

		if (!is_admin() && !is_feed())
		{
			// instead of widgetizeing our shortcodes, it's
			// easyer to just enable them in texts of widgets
			if ( class_exists('WP_Embed') )
				add_filter('widget_text',				array(&$this, 'MMSG_widget_text'),				7);
			add_filter('script_loader_src',				array(&$this, 'MMSG_script_loader_src'),		10, 2);
			add_filter('style_loader_src',				array(&$this, 'MMSG_style_loader_src'), 		10, 2);
			add_filter('tiny_mce_before_init',			array(&$this, 'MMSG_tiny_mce_before_init'));
		}
		else if (is_admin())
		{
			if ( class_exists('WP_Embed') )
				add_filter('widget_update_callback',	array(&$this, 'MMSG_widget_update_callback'),	1, 4);
		}

		// frontend extensions
		add_action('wp_head',							array(&$this, 'MMSG_wp_head'));
/*
		// widgets
		add_action('widgets_init',						array(&$this, 'MMSG_widgets_init'));

		// WP query
		add_filter('query',								array(&$this, 'MMSG_query'));
		add_filter('query_vars',						array(&$this, 'MMSG_query_vars'));

		add_action('wp_footer',							array(&$this, 'MMSG_wp_footer'));
		add_action('wp_print_styles',					array(&$this, 'MMSG_wp_print_styles'));
*/
	}

	// -------------------------------------------------------------------------------------
	// HOOKS
	// -------------------------------------------------------------------------------------

	/**
	 * INSTALL
	 *
	 * check required versions and DB dependencies
	 */
	function MMSG_activation_hook()
	{
		global $wpdb;

		// current options
		$curr_version    = get_option('MMSG_version');
		$curr_db_version = get_option('MMSG_DB_version');

//		require_once(require(dirname(__FILE__).'/lib/Debug.php');
//		Debug::getInstance('debug.log')->filog($curr_version, $curr_db_version);

		// check DB
		if (!$curr_db_version || $curr_db_version == '' || $curr_db_version < MMSG_DB_VERSION)
		{
			require_once(ABSPATH.'wp-admin/includes/upgrade.php');

			update_option('MMSG_install_time', time());

			$sql = "CREATE TABLE ".$wpdb->prefix.MMSG_PREFIX.'shortcodes'." (
						id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
						shortcode VARCHAR(255) NOT NULL,
						value LONGTEXT NOT NULL,
						type VARCHAR(255) NOT NULL,
						dependencies VARCHAR(255),
						UNIQUE KEY id (id),
						UNIQUE (shortcode)
					);";
			dbDelta($sql);

			$sql = "CREATE TABLE ".$wpdb->prefix.MMSG_PREFIX.'scripts'." (
						id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
						name VARCHAR(255) NOT NULL,
						source VARCHAR(255),
						dependencies VARCHAR(255),
						version VARCHAR(255),
						foot tinyint(1) NOT NULL,
						type VARCHAR(255) NOT NULL,
						codes VARCHAR(255),
						UNIQUE KEY id (id),
						UNIQUE (name)
					);";
			dbDelta($sql);

			$sql = "CREATE TABLE ".$wpdb->prefix.MMSG_PREFIX.'styles'." (
						id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
						name VARCHAR(255) NOT NULL,
						source VARCHAR(255),
						dependencies VARCHAR(255),
						version VARCHAR(255),
						media VARCHAR(255),
						type VARCHAR(255) NOT NULL,
						codes VARCHAR(255),
						UNIQUE KEY id (id),
						UNIQUE (name)
					);";
			dbDelta($sql);

			$this->insert_DB_defults();
		}

		// update options
		update_option('MMSG_version',		MMSG_VERSION);
		update_option('MMSG_DB_version',	MMSG_DB_VERSION);
	}

	/**
	 * UNINSTALL
	 *
	 * this is for bulk action, to remove, to uninstall custom deactivation page is present
	 */
	function MMSG_deactivation_hook()
	{
		// will also be deleted in custom deactivation page
		delete_option('MMSG_version');
	}

	// -------------------------------------------------------------------------------------

	/**
	 * INIT MMSG
	 *
	 * Registers all script and styles to handle dependency trees
	 */
	function MMSG_init()
	{
		if (!is_admin() && !is_feed())
		{
			// register styles
			if (!isset(MementoShortcodeGenerator::$styles))
				MementoShortcodeGenerator::$styles = $this->list_styles();
			foreach (MementoShortcodeGenerator::$styles as $style)
			{
				if (is_object($style))
				{
					$source = stripslashes($style->source);
					if (strlen($source) > 0)
						wp_register_style(
							$style->name,
							$source,
							preg_split('/,/', preg_replace('/\s+/', '', $style->dependencies), -1, PREG_SPLIT_NO_EMPTY),
							$style->version,
							$style->media
						);
				}
			}

			// register scripts
			if (!isset(MementoShortcodeGenerator::$scripts))
				MementoShortcodeGenerator::$scripts = $this->list_scripts();
			foreach (MementoShortcodeGenerator::$scripts as $script)
			{
				if (is_object($script))
				{
					$source = stripslashes($script->source);
					if (strlen($source) > 0)
						wp_register_script(
							$script->name,
							$source,
							preg_split('/,/', preg_replace('/\s+/', '', $script->dependencies), -1, PREG_SPLIT_NO_EMPTY),
							$script->version,
							$script->foot
						);
				}
			}
		}
	}

	// -------------------------------------------------------------------------------------


	/**
	 * FILTERING POSTS
	 *
	 * preprocessing posts to know what we will use on page
	 */
	function MMSG_the_posts( $posts )
	{
		if (!is_admin() && !is_feed())
		{
			// !!! DO NOT PRINT ANYTHING HERE IN PRODUCTION !!!
			// !!! NOKIA GETS HOOKED WITH SOME HEADER STUFF !!!

			if (!isset(MementoShortcodeGenerator::$shortcodes))
				MementoShortcodeGenerator::$shortcodes = $this->list_shortcodes();

			// fetch all widgets' used shortcodes,
			// how to parse wicth of them we will use on current page?
			$codes_to_execute = array();
			if ($widget_codes = get_option(MMSG_WIDGET_CODES))
			{
				$sidebars_widgets = wp_get_sidebars_widgets(true);
				if (isset($sidebars_widgets['wp_inactive_widgets']))
					unset($sidebars_widgets['wp_inactive_widgets']);

				// I like the way this looks... :]
				foreach ($sidebars_widgets as $sidebar => $swidgets)
	//				if (in_array($sidebar, $sidebars_to display))
						foreach ($swidgets as $swidget_id)
							if (array_key_exists($swidget_id, $widget_codes))
								foreach ((array)$widget_codes[$swidget_id] as $widget_sc)
									if (!in_array($widget_sc, $codes_to_execute))
										$codes_to_execute[] = $widget_sc;
			}

			foreach ($posts as $post)
				$codes_to_execute = $this->parse_code_dependencies($post->post_content, true, $codes_to_execute);

			// now that we know witch codes of ours will be executed on the page it is time to find recursive codes
			$j = count($codes_to_execute);
			for ($i=0; $i<$j; $i++)
			{
				$code = $codes_to_execute[$i];
				$deps = preg_split('/,/', MementoShortcodeGenerator::$shortcodes[$code]->dependencies, -1, PREG_SPLIT_NO_EMPTY);
				foreach ($deps as $dep)
				{
					if (!in_array(MMSG_PREFIX.$dep, $codes_to_execute))
					{
						$codes_to_execute[] = MMSG_PREFIX.$dep;
						$j++;
					}
				}
			}

			// fetch styles
			if (!isset(MementoShortcodeGenerator::$styles))
				MementoShortcodeGenerator::$styles = $this->list_styles();
			foreach (MementoShortcodeGenerator::$styles as $style)
			{
				if (is_object($style))
				{
					if ($style->type == 'global')
					{
						wp_enqueue_style($style->name);
					}
					else
					{
						$deps = preg_split('/,/', $style->codes, -1, PREG_SPLIT_NO_EMPTY);
						foreach ($deps as &$dep)
							$dep = MMSG_PREFIX.$dep;

						if ( array_intersect($codes_to_execute, $deps) )
						{					
							// WE DO NOT KNOW ABOUT DEPENDENT MMSG STYLES, THEY ARE NOT REGISTERED ANYWHERE
							// IF NOT SET AS SHORTCODE DEPENDENCY, WILL NOT PRINT
							wp_enqueue_style($style->name);
						}
					}
				}
			}

			// fetch dependent scripts
			if (!isset(MementoShortcodeGenerator::$scripts))
				MementoShortcodeGenerator::$scripts = $this->list_scripts();
			foreach (MementoShortcodeGenerator::$scripts as $script)
			{
				if (is_object($script))
				{
					if ($script->type == 'global')
					{
						wp_enqueue_script($script->name);
					}
					else
					{
						$deps = preg_split('/,/', $script->codes, -1, PREG_SPLIT_NO_EMPTY);
						foreach ($deps as &$dep)
							$dep = MMSG_PREFIX.$dep;
						if ( array_intersect($codes_to_execute, $deps) )
						{
							// WE DO NOT KNOW ABOUT DEPENDENT MMSG SCRIPTS, THEY ARE NOT REGISTERED ANYWHERE
							// IF NOT SET AS SHORTCODE DEPENDENCY, WILL NOT PRINT
							wp_enqueue_script($script->name);
						}
					}
				}
			}
		}

		return $posts;
	}

	/**
	 * FILTERING CONTENT
	 *
	 * running only our shortcodes
	 */
	function MMSG_the_content($content)
	{
		return $this->shortcode_hack( $content );
	}

	/**
	 * FILTERING WIDGET TEXT
	 *
	 * running only our shortcodes
	 */
	function MMSG_widget_text($content)
	{
		return $this->shortcode_hack( $content );
		//return do_shortcode($this->shortcode_hack( $content ));
	}

	/**
	 * FILTERING WIDGET UPDATE
	 *
	 * fetching dependencies at save
	 */
	function MMSG_widget_update_callback($instance, $new_instance, $old_instance, $widgetclass)
	{
//		require_once(dirname(__FILE__).'/lib/Debug.php');
//		Debug::getInstance('_debug.log')->filog($instance, $new_instance, $old_instance, $widgetclass);

		if ($widgetclass->id_base == 'text')
		{
			if (!isset(MementoShortcodeGenerator::$shortcodes))
				MementoShortcodeGenerator::$shortcodes = $this->list_shortcodes();

			if (!$widget_codes = get_option(MMSG_WIDGET_CODES));
				$widget_codes = array();

			$widget_codes[$widgetclass->id] = $this->parse_code_dependencies($new_instance['text'], true, $codes_to_execute);

			// Re-save the widget settings but this time with the shortcode contents encoded
			//$new_instance['text']	= $this->encode_shortcode_contents( $new_instance['text'] );
			//$instance				= $widgetclass->update( $new_instance, $old_instance );

			$instance['MMSG_dependency_parsed'] = true;

			update_option(MMSG_WIDGET_CODES, $widget_codes);
		}

		return $instance;
	}

	// -------------------------------------------------------------------------------------

	/**
	 * TINYMCE TABFOCUS
	 *
	 * Searches the list of TinyMCE plugins for 'tabfocus' and removes it when found. 
	 */
	function MMSG_tiny_mce_before_init($initArray)
	{
	    $initArray['plugins'] = preg_replace("|[,]+tabfocus|i", "", $initArray['plugins']);
	    return $initArray;
	}

	// -------------------------------------------------------------------------------------

	/**
	 * STYLE SOURCE
	 *
	 * remove version from styles, where it is not present
	 * by default for empty versions WP adds its own version in query string
	 */
	function MMSG_style_loader_src($src, $handle = null)
	{
		global $wp_styles;

		if ($handle)
		{
			if (empty($wp_styles->registered[$handle]->ver))
				$src = remove_query_arg('ver', $src);
			else
				$src = add_query_arg(array('ver' => $wp_styles->registered[$handle]->ver), $src);
		}

		return $src;
	}

	// -------------------------------------------------------------------------------------

	/**
	 * SCRIPT SOURCE
	 *
	 * remove version from scripts, where it is not present
	 * by default for empty versions WP adds its own version in query string
	 */
	function MMSG_script_loader_src($src, $handle = null)
	{
		global $wp_scripts;

		if ($handle)
		{
			if (empty($wp_scripts->registered[$handle]->ver))
				$src = remove_query_arg('ver', $src);
			else
				$src = add_query_arg(array('ver' => $wp_scripts->registered[$handle]->ver), $src);
		}

		return $src;
	}

	/**
	 * EXTENDING TEMPLATE HEAD
	 *
	 * may be needed in a later time
	 */
	function MMSG_wp_head()
	{
//		$current_path = get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) .'/';
	}

	/**
	 * EXTENDING TEMPLATE FOOTER
	 *
	 * handling shortcode dependant footer scripts here
	 */
	function MMSG_wp_footer()
	{
		//
	}

	// -------------------------------------------------------------------------------------

	/**
	 * ADMIN MENU
	 *
	 * extend the admin frontend with our buttons
	 */
	function MMSG_admin_menu()
	{
		add_submenu_page(
			'options-general.php',
			'MMSG '.__('Settings', MMSG_PLUGIN_NAME),
			'MMSG '.__('Settings', MMSG_PLUGIN_NAME),
			'manage_options',
			plugin_basename(__FILE__),
			array(&$this, 'MMSG_settings_page')
		);

		add_menu_page(
			'MMSG '.__('Admin', MMSG_PLUGIN_NAME),
			'MMSG '.__('Codes', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_codes.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_codes.php',
			'MMSG '.__('Shortcode List', MMSG_PLUGIN_NAME),
			__('Shortcodes', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_codes.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_codes.php',
			'MMSG '.__('Add Plain Code', MMSG_PLUGIN_NAME),
			__('Add New Plain', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_code_plain.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_codes.php',
			'MMSG '.__('Add Rich Code', MMSG_PLUGIN_NAME),
			__('Add New Rich', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_code_rich.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_codes.php',
			'MMSG '.__('Add Advanced Code', MMSG_PLUGIN_NAME),
			__('Add New Advanced', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_code_advanced.php'
		);

		add_menu_page(
			'MMSG '.__('Admin', MMSG_PLUGIN_NAME),
			'MMSG '.__('Scripts', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_scripts.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_scripts.php',
			'MMSG '.__('Scripts', MMSG_PLUGIN_NAME),
			__('Scripts', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_scripts.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_scripts.php',
			'MMSG '.__('Add Global Script', MMSG_PLUGIN_NAME),
			__('Add New Global', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_script_global.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_scripts.php',
			'MMSG '.__('Add Dependent Script', MMSG_PLUGIN_NAME),
			__('Add New Dependent', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_script_dependent.php'
		);

		add_menu_page(
			'MMSG '.__('Admin', MMSG_PLUGIN_NAME),
			'MMSG '.__('Styles', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_styles.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_styles.php',
			'MMSG '.__('Styles', MMSG_PLUGIN_NAME),
			__('Styles', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/list_styles.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_styles.php',
			'MMSG '.__('Add Global Style', MMSG_PLUGIN_NAME),
			__('Add New Global', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_style_global.php'
		);
		add_submenu_page(
			MMSG_PLUGIN_NAME.'/admin/list_styles.php',
			'MMSG '.__('Add Dependent Style', MMSG_PLUGIN_NAME),
			__('Add New Dependent', MMSG_PLUGIN_NAME),
			'manage_options',
			MMSG_PLUGIN_NAME.'/admin/add_new_style_dependent.php'
		);
	}

	/**
	 * ADMIN HEAD (TinyMCE & jQuery dragdroplist)
	 */
	function MMSG_admin_head()
	{
		$base = basename($_SERVER['SCRIPT_NAME']);

		// ability to show advanced WP TinyMCE editor
		if ($base == 'admin.php')
		{
			if ($_GET['page'] == 'Memento-SG/admin/add_new_code_rich.php' ||
				$_GET['page'] == 'Memento-SG/admin/list_codes.php')
			{
				wp_enqueue_script('common');
				wp_enqueue_script('jquery-color');
				wp_print_scripts('editor');
				if (function_exists('add_thickbox'))
					add_thickbox();
				wp_print_scripts('media-upload');
				wp_admin_css();
				wp_enqueue_script('utils');
				do_action('admin_print_styles-post-php');
				do_action('admin_print_styles');
				wp_tiny_mce();
			}
		}

		// ability to show jquery dragdroplist
		if  ( $base == 'admin.php' &&
			($_GET['page'] == 'Memento-SG/admin/add_new_code_advanced.php'       ||
				$_GET['page'] == 'Memento-SG/admin/list_codes.php'               ||
				$_GET['page'] == 'Memento-SG/admin/add_new_script_dependent.php' ||
				$_GET['page'] == 'Memento-SG/admin/list_scripts.php'             ||
				$_GET['page'] == 'Memento-SG/admin/add_new_style_dependent.php'  ||
				$_GET['page'] == 'Memento-SG/admin/list_styles.php') )
		{
			print '<link rel="stylesheet" href="'.MMSG_DRAGDROPLIST_CSS.'" type="text/css" media="all" />'."\n";
		}
	}

	/**
	 * ADMIN FOOTER (codepress & jQuery dragdroplist)
	 */
	function MMSG_admin_footer()
	{
		global $file;

		$base = basename($_SERVER['SCRIPT_NAME']);

		$type = strstr(basename($file), '.');
		switch ($type)
		{
			case '.php':
				$type = 'php';
			break;
			case '.css':
				$type = 'css';
			break;
			case '.js':
				$type = 'javascript';
			break;
			case '.html':
			case '.htm':
				$type = 'html';
			break;
			default:
				$type = null;
			break;
		}

		$MM_advanced_editor = false;
		if  ( $base == 'admin.php' &&
			($_GET['page'] == 'Memento-SG/admin/add_new_code_advanced.php' ||
				$_GET['page'] == 'Memento-SG/admin/add_new_code_plain.php' ||
				$_GET['page'] == 'Memento-SG/admin/list_codes.php') )
		{
			$MM_advanced_editor = true;
			$type               = 'php_snippet';
		}

		// ability to show jquery dragdroplist
		if  ( $base == 'admin.php' &&
			($_GET['page'] == 'Memento-SG/admin/add_new_code_advanced.php'       ||
				$_GET['page'] == 'Memento-SG/admin/list_codes.php'               ||
				$_GET['page'] == 'Memento-SG/admin/add_new_script_dependent.php' ||
				$_GET['page'] == 'Memento-SG/admin/list_scripts.php'             ||
				$_GET['page'] == 'Memento-SG/admin/add_new_style_dependent.php'  ||
				$_GET['page'] == 'Memento-SG/admin/list_styles.php') )
		{
			print "<!-- MMSG jQuery drag and drop list support -->\n";
			print '<script type="text/javascript" src="'.MMSG_JQUERYUI_URL.'"></script>'."\n";
			print '<script type="text/javascript" src="'.MMSG_BROWSERDETECT_URL.'"></script>'."\n";
			print '<script type="text/javascript" src="'.MMSG_TINYSORT_URL.'"></script>'."\n";
			print '<script type="text/javascript" src="'.MMSG_DRAGDROPLIST_URL.'"></script>'."\n";
			print '<script type="text/javascript" src="'.MMSG_INIT_DEPENDENCIES_URL.'"></script>'."\n";
		}

		// ability to show codepress colored editor fields
		if ($base == 'plugin-editor.php' ||
			$base == 'theme-editor.php'  ||
			$MM_advanced_editor)
		{
			if ($type)
			{
				print
<<<PHP
<!-- MMSG Codepress support -->
<script type="text/javascript">
// <![CDATA[
	var wpcp_ta = document.getElementById("newcontent");
	if (wpcp_ta) {
		wpcp_ta.form.onsubmit = function() {
			//do some hacky stuff because codepress screws up our form
			var inp = document.createElement("input");
			inp.setAttribute("type", "hidden");
			inp.setAttribute("name", "newcontent");
			inp.setAttribute("value", newcontent.getCode());
			wpcp_ta.form.appendChild(inp);
			return true;
		}
		wpcp_ta.className = "codepress $type";

PHP;
				print 'wpcp_href="'.MMSG_CODEPRESS_URL.'";';
				print
<<<PHP

		var wpcp_ie = window.ActiveXObject;
		var wpcp_safari = (document.childNodes && !document.all && !navigator.taintEnabled);
		if (document.body || (!wpcp_safari && !wpcp_ie)) {
			var wpcp_parent = document.getElementsByTagName("head")[0];
			var wpcp_node = document.createElement('script');
			wpcp_node.type = 'text/javascript';
			wpcp_node.src = wpcp_href;
			wpcp_parent.appendChild(wpcp_node);
		}
		else {
			document.write('<script type="text/javascript" src="'+wpcp_href+'"><\script>');
		}
	}
// ]]>
</script>
PHP;
			}
		}
	}

	/**
	 * ADMIN PLUGIN LINKS
	 */
	function MMSG_plugin_action_links($links)
	{
		$links[0] = '<a href="options-general.php?page='.MMSG_PLUGIN_NAME.'/'.MMSG_PLUGIN_NAME.'.php&amp;deactivate=true">'.
			__('Deactivate') .'</a>';
		$settings_link = '<a href="options-general.php?page='.MMSG_PLUGIN_NAME.'/'.MMSG_PLUGIN_NAME.'.php">'.
			__('Settings').'</a>';

		array_unshift($links, $settings_link);

		return $links;
	}

	// -------------------------------------------------------------------------------------
	// CUSTOM SHORTCODE PROCESSING
	// -------------------------------------------------------------------------------------

	/**
	 * SHORTCODE HACK
	 *
	 * hack to process the plugins shortcodes first
	 */
	function shortcode_hack( $content )
	{
		if (!isset(MementoShortcodeGenerator::$shortcodes))
			MementoShortcodeGenerator::$shortcodes = $this->list_shortcodes();

		global $shortcode_tags;

		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		foreach (MementoShortcodeGenerator::$shortcodes as $sc)
		{
			// !!! else it could be associative string value like the regex search pattern !!!
			if (is_object($sc))
			{
				$code = MMSG_PREFIX.$sc->shortcode;
				$val = stripslashes($sc->value);

				switch ($sc->type)
				{
					case 'plain':
					case 'rich':

						$val = str_replace("'", "\\'", $val);
						// !!! notice "'"s around $val and $code !!!
						add_shortcode(
							$code,
							create_function(
								'$atts, $content = null',
<<<PHP
\$out = '$val';
return do_shortcode(\$out);
PHP
							)
						);

					break;

					case 'advanced':

						try
						{
							$func = create_function(
										'$atts, $content = null',
<<<PHP
\$code = '$code';
\$out  = '';
$val
return do_shortcode(\$out);
PHP
									);
						}
						catch (Exception $e)
						{
							add_shortcode(
								$code,
								create_function(
									'$atts, $content = null',
<<<PHP
\$out = "-> Error: <code>$code</code> shortcode is not compiling.";
return \$out;
PHP
								)
							);
							break;
						}

						if ($func)
						{
							// let the sky fall on my head...
							add_shortcode($code, $func);
						}
						else
						{
							add_shortcode(
								$code,
								create_function(
									'$atts, $content = null',
<<<PHP
\$out = "-> Error: <code>$code</code> shortcode is not compiling.";
return \$out;
PHP
								)
							);
						}

					break;
				}
			}
		}

		try
		{
			$content = do_shortcode( $content );
		}	
		catch (Exception $e)
		{
			print '<br/>Error: <code>do_shortcode exception caught.</code><br/>';
		}

		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

	// -------------------------------------------------------------------------------------
	// HELPERS
	// -------------------------------------------------------------------------------------

	// DEFAULTS
	function insert_DB_defults()
	{
		global $wpdb;

		// backward compatibility
		$wpdb->query(
			"UPDATE ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
				SET type = 'global'
				WHERE type = ''"
		);

		$wpdb->query(
			"UPDATE ".$wpdb->prefix.MMSG_PREFIX.'styles'."
				SET type = 'global'
				WHERE type = ''"
		);
	}

	function WP_version()
	{
		global $wp_version;
		$wp_ok  =  version_compare($wp_version, MMSG_MIN_WP, '>=');

		if (!$wp_ok)
		{
			add_action(
				'admin_notices', 
				create_function(
					'',
<<<PHP
global \$MMSG;
printf (
	'<div id="message" class="error"><p><strong>'.
	__('Sorry, MMSG works only under WordPress %s or higher', MMSG_PLUGIN_NAME).
	'</strong></p></div>',
MMSG_MIN_WP);
PHP
				)
			);
			return false;
		}
		return true;
	}

	function clean_string($code)
	{
		// everything, that is bad or used
		$bad = array(
			"'", '"', "\\", '/', '[', ']', '(', ')', '<', '>',
			' ', '!', '@', '#', '$', '%', '^', '&', '*', '?', '`', '~',
			'num_styles', 'num_scripts', 'num_codes', 'tag_regexp'
		);
		return strtolower(str_replace($bad, '', $code));
	}

	// for comma separated string values
	function clean_white($code)
	{
		return strtolower(preg_replace('/\s+/', '', $code));
	}

	// to keep tab structure of advanced code
	function encode_advanced_content($code)
	{
		return str_replace(
			array_keys($this->specialchars),
			array_values($this->specialchars),
			htmlspecialchars($code)
		);
	}

	// to keep tab structure of advanced code
	function decode_advanced_content($code)
	{
		return str_replace(
			array_values($this->specialchars),
			array_keys($this->specialchars),
			htmlspecialchars_decode($code)
		);
	}
	
	// find dependent shortcodes in plain and rich codes
	function parse_code_dependencies($to_parse, $return_array = false, $array = null)
	{
		if (!isset(MementoShortcodeGenerator::$shortcodes))
			MementoShortcodeGenerator::$shortcodes = $this->list_shortcodes();

		if ($return_array)
			$dep_tmp = $array;
		else
			$dep_tmp = array();

		// get_shortcode_regex() gets the regex for registered shortcodes,
		// ours will not appear in the list so we create regex the way Wordpress does it
		// when querying database					
		preg_match_all(
			'/'.MementoShortcodeGenerator::$shortcodes['tag_regexp'].'/s',
			$to_parse,
			$matches
		);

		if (is_array($matches[2]))
		{
			foreach($matches[2] as $match)
			{
				if (!in_array($match, $dep_tmp))
				{
					if ($return_array)
						$dep_tmp[] = $match;
					else
						$dep_tmp[] = preg_replace('/^'.MMSG_PREFIX.'/', '', $match);
				}
			}
		}

		if ($return_array)
			return $dep_tmp;
		else
			return implode(",", $dep_tmp);
	}

	// inform user
	function fade_msg($msg, $echo = true)
	{
		if ($echo)
			echo "\n\n".'<div class="updated fade"><p><strong>'.$msg."</strong></p></div>\n";
		else
			return "\n\n".'<div class="updated fade"><p><strong>'.$msg."</strong></p></div>\n";
	}

	function MMSG_settings_page()
	{
		if (key_exists('deactivate', $_GET) && $_GET['deactivate'] == 'true')
			require_once 'admin/deactivate.php';
		else
			require_once 'admin/settings.php';
	}

	// -------------------------------------------------------------------------------------
	// CONNECTORS
	// -------------------------------------------------------------------------------------

	/**
	 * SHORTCODES
	 *
	 * function names speak for themselves
	 */
	function list_shortcodes($start = 0, $limit = 0)
	{
		if ($limit != 0)
			$limit_q = " LIMIT $start, $limit";
		else
			$limit_q = '';

		global $wpdb;
		$assoc_codes = array();
		$codes = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'shortcodes'."
									ORDER BY id $limit_q");

		$tag_names = array();
		foreach ($codes as $code)
		{
			if ($code->type == 'advanced')
			{
				$code->value = $this->decode_advanced_content($code->value);
			}

			$tag_names[] = MMSG_PREFIX.$code->shortcode;

			$assoc_codes[MMSG_PREFIX.$code->shortcode] = $code;
		}

		// creating search pattern
		$tag_regexp                = join( '|', array_map('preg_quote', $tag_names) );
		$assoc_codes['num_codes']  = count($codes);
		$assoc_codes['tag_regexp'] = '(.?)\[('.$tag_regexp.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';

		return $assoc_codes;
	}

	function get_shortcode($id)
	{
		global $wpdb;

		$code = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'shortcodes'."
								WHERE id=$id");

		if ($code->type == 'advanced')
			$code->value = $this->decode_advanced_content($code->value);

		return $code;
	}

	function exists_shortcode($code)
	{
		global $wpdb;
		$code = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix.MMSG_PREFIX.'shortcodes'."
									WHERE shortcode='$code'");

		if (!empty($code))
			return $code;
		else
			return false;
	}

	function add_shortcode($code, $value, $dependencies, $type)
	{
		global $wpdb;

		if ($type == 'advanced')
			$value = $this->encode_advanced_content($value);

		//print $value;

		$wpdb->insert(
			$wpdb->prefix.MMSG_PREFIX.'shortcodes',
			array(
				'shortcode'    => $code,
				'value'        => $value,
				'type'         => $type,
				'dependencies' => $dependencies
			),
			array('%s', '%s', '%s', '%s')
		);

		return $wpdb->insert_id;
	}

	function update_shortcode($id, $code, $value, $dependencies, $type)
	{
		global $wpdb;

		if ($type == 'advanced')
			$value = $this->encode_advanced_content($value);

		//print $value;

		$wpdb->update(
			$wpdb->prefix.MMSG_PREFIX.'shortcodes',
			array(
				'shortcode'    => $code,
				'value'        => $value,
				'type'         => $type,
				'dependencies' => $dependencies
			),
			array(
				'id' => $id
			),
			array('%s','%s','%s','%s')
		);

		return true;		
	}

	function remove_shortcode($id)
	{
		global $wpdb;
		$wpdb->query("DELETE FROM ".$wpdb->prefix.MMSG_PREFIX.'shortcodes'."
						WHERE id=$id
						LIMIT 1");

		return true;
	}


	// -------------------------------------------------------------------------------------

	/**
	 * SCRIPTS
	 *
	 * function names speak for themselves
	 */
	function list_scripts($start = 0, $limit = 0)
	{
		if ($limit != 0)
			$limit_q = " LIMIT $start, $limit";
		else
			$limit_q = '';

		global $wpdb;
		$assoc_scripts = array();
		$scripts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
									ORDER BY id
									$limit_q");

		foreach ($scripts as $script)
			$assoc_scripts[$script->name] = $script;

		$assoc_scripts['num_scripts'] = count($scripts);

		return $assoc_scripts;
	}

	function get_script($id)
	{
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
								WHERE id=$id");
	}

	function exists_script($name)
	{
		global $wpdb;
		$script = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
									WHERE name='$name'");

		if (!empty($script))
			return $script;
		else
			return false;
	}

	function add_script($name, $source, $dependencies, $version, $foot, $type, $codes)
	{
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix.MMSG_PREFIX.'scripts',
			array(
				'name'			=> $name,
				'source'		=> $source,
				'dependencies'	=> $dependencies,
				'version'		=> $version,
				'foot'			=> $foot,
				'type'			=> $type,
				'codes'			=> $codes
			),
			array('%s', '%s', '%s', '%s', '%s')
		);

		return $wpdb->insert_id;
	}

	function update_script($id, $name, $source, $dependencies, $version, $foot, $type, $codes)
	{
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix.MMSG_PREFIX.'scripts',
			array(
				'name'			=> $name,
				'source'		=> $source,
				'dependencies'	=> $dependencies,
				'version'		=> $version,
				'foot'			=> $foot,
				'type'			=> $type,
				'codes'			=> $codes
			),
			array(
				'id' => $id
			),
			array('%s', '%s', '%s', '%s', '%s')
		);

		return true;
	}

	function remove_script($id)
	{
		global $wpdb;
		$wpdb->query("DELETE FROM ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
						WHERE id=$id
						LIMIT 1");

		return true;
	}
	
	// -------------------------------------------------------------------------------------

	/**
	 * STYLES
	 *
	 * function names speak for themselves
	 */
	function list_styles($start = 0, $limit = 0)
	{
		if ($limit != 0)
			$limit_q = " LIMIT $start, $limit";
		else
			$limit_q = '';

		global $wpdb;
		$assoc_styles = array();
		$styles = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'styles'."
									ORDER BY id
									$limit_q");

		foreach ($styles as $style)
			$assoc_styles[$style->name] = $style;

		$assoc_styles['num_styles'] = count($styles);

		return $assoc_styles;
	}

	function get_style($id)
	{
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.MMSG_PREFIX.'styles'."
								WHERE id=$id");
	}

	function exists_style($name)
	{
		global $wpdb;
		$style = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix.MMSG_PREFIX.'styles'."
									WHERE name='$name'");

		if (!empty($style))
			return $style;
		else
			return false;
	}

	function add_style($name, $source, $dependencies, $version, $media, $type, $codes)
	{
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix.MMSG_PREFIX.'styles',
			array(
				'name'			=> $name,
				'source'		=> $source,
				'dependencies'	=> $dependencies,
				'version'		=> $version,
				'media'			=> $media,
				'type'			=> $type,
				'codes'			=> $codes
			),
			array('%s', '%s', '%s', '%s', '%s')
		);

		return $wpdb->insert_id;
	}

	function update_style($id, $name, $source, $dependencies, $version, $media, $type, $codes)
	{
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix.MMSG_PREFIX.'styles',
			array(
				'name'			=> $name,
				'source'		=> $source,
				'dependencies'	=> $dependencies,
				'version'		=> $version,
				'media'			=> $media,
				'type'			=> $type,
				'codes'			=> $codes
			),
			array(
				'id' => $id
			),
			array('%s', '%s', '%s', '%s', '%s')
		);

		return true;
	}

	function remove_style($id)
	{
		global $wpdb;
		$wpdb->query("DELETE FROM ".$wpdb->prefix.MMSG_PREFIX.'scripts'."
						WHERE id=$id
						LIMIT 1");

		return true;
	}

	// -------------------------------------------------------------------------------------	
/*
	// LOAD STYLES ON FRONTEND PAGES
	function MMSG_wp_print_styles()
	{
		//
	}

	// -------------------------------------------------------------------------------------

	// PROCESSING WIDGETS
	function MMSG_widgets_init()
	{
//		require_once(dirname(__FILE__).'/lib/Debug.php');
		$widgets = wp_get_sidebars_widgets();
	}

	// PROCESSING WIDGETS
	function MMSG_widgets_init()
	{
//		delete_option(MMSG_MULTI_PREFIX);

		require_once(dirname(__FILE__).'/lib/Debug.php');
		$debug		= Debug::getInstance('_debug.log');
		$name       = 'MMSG '.__('Sidebar Widget', MMSG_PLUGIN_NAME);
		$registered	= false;
		$debug->filog('MMSG_widgets_init');

		$widget_ops	= array(
			'classname'   => 'MMSG_multiwidget',
			'description' => __('Allows insertion of generated shortcodes into the sidebar.', MMSG_PLUGIN_NAME)
		);
		$control_ops = array(
			'width'   => 200,
			'height'  => 200,
			'id_base' => MMSG_MULTI_PREFIX
		);

		if (!$options = get_option(MMSG_MULTI_PREFIX))
			$options = array();

		foreach ( array_keys($options) as $key )
		{
			if ( isset($options[$key]['shortcode']) && $options[$key]['shortcode'] != '')
			{
				wp_register_sidebar_widget(
					MMSG_MULTI_PREFIX.'-'.$key,
					$name,
					array( &$this, 'MMSG_sidebar_widget' ),
					$widget_ops,
					array( 'number' => $key )
				);

				wp_register_widget_control(
					MMSG_MULTI_PREFIX.'-'.$key,
					$name,
					array( &$this, 'MMSG_widget_control' ),
					$control_ops,
					array( 'number' => $key )
				);

				$debug->filog(
					'wp_register_sidebar_widget MULTI',
					MMSG_MULTI_PREFIX.'-'.$key,
					$name,
					$key
				);

				$registered = true;
			}
		}

		if (!$registered)
		{
			wp_register_sidebar_widget(
				MMSG_MULTI_PREFIX.'-1',
				$name,
				array( &$this, 'MMSG_sidebar_widget' ),
				$widget_ops,
				array( 'number' => -1 )
			);

			wp_register_widget_control(
				MMSG_MULTI_PREFIX.'-1',
				$name,
				array( &$this, 'MMSG_widget_control' ),
				$control_ops,
				array( 'number' => -1 )
			);	

			$debug->filog(
				'wp_register_sidebar_widget DEFAULT',
				MMSG_MULTI_PREFIX.'-1',
				$name,
				-1
			);
		}
	}

	// WIDGET CONTROL
	function MMSG_widget_control( $widget_args = 1 )
	{
		require_once(dirname(__FILE__).'/lib/Debug.php');
		$debug = Debug::getInstance('_debug.log');
		$debug->filog('MMSG_widget_control');

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );

		$widget_args		= wp_parse_args( $widget_args, array( 'number' => 1 ) );
		$widget_number		= $widget_args['number'];
		$options			= get_option(MMSG_MULTI_PREFIX);
		if( !is_array($options) )
		      $options = array();

		if (!isset(MementoShortcodeGenerator::$multi_updated))
			MementoShortcodeGenerator::$multi_updated = false;

		if (!MementoShortcodeGenerator::$multi_updated && !empty($_POST['sidebar']))
		{
			// WITCH SIDEBAR

			$sidebar			= (string) $_POST['sidebar'];
			$sidebars_widgets	= wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar = $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			// REMOVE WIDGETS FROM AFFECTED SIDEBAR

			foreach ($this_sidebar as $_widget_id)
			{
				if ( $wp_registered_widgets[$_widget_id]['callback'] == 'MMSG_sidebar_widget'
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) )
				{
					$number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( MMSG_MULTI_PREFIX.'-'.$number, $_POST['widget-id'] ) )
						unset($options[$number]);
				}
			}

			// STORE AFFECTED WIDGETS

			foreach( (array)$_POST['widget-'.MMSG_MULTI_PREFIX] as $number=>$new_instance)
			{
				if ( !isset($options[$number]) && isset($new_instance['title']) && $new_instance['title'] != '' )
				{
					$spec_title			= wp_specialchars( $new_instance['title'] );
					$shortcode			= $new_instance['shortcode'];
					$options[$number]	= array(
						 							'title'     => $spec_title,
													'shortcode' => $shortcode
												);

					MementoShortcodeGenerator::$multi_updated = true;
				}
			}

			if (MementoShortcodeGenerator::$multi_updated == true)
				update_option(MMSG_MULTI_PREFIX, $options);
		}

		if ( $widget_number == -1 )
		{
			$w_title		= '';
			$shortcode		= '';
			$widget_number	= '%i%';
		}
		else
		{
			$w_title   = attribute_escape($options[$widget_number]['title']);
			$shortcode = attribute_escape($options[$widget_number]['shortcode']);
		}

		?>
			<p>
				<label>
					<?php _e('Title', MMSG_PLUGIN_NAME); ?>:<br />
					<input class="widefat" id="<?= $this->get_widget_field_id($widget_number, 'title'); ?>" name="<?= $this->get_widget_field_name($widget_number, 'title'); ?>" type="text" value="<?= $w_title; ?>" />
				</label>
				<br /><br />
				<label>
					<?php _e('Shortcode', MMSG_PLUGIN_NAME); ?>:<br />
					<select id="<?= $this->get_widget_field_id($widget_number, 'shortcode'); ?>" name="<?= $this->get_widget_field_name($widget_number, 'shortcode'); ?>">
					<?
						if (!isset(MementoShortcodeGenerator::$shortcodes))
							MementoShortcodeGenerator::$shortcodes = $this->list_shortcodes();

						foreach (MementoShortcodeGenerator::$shortcodes as $sc)
						{
							// !!! else it could be associative string value like the regex search pattern !!!
							if (is_object($sc))
							{
								if ($shortcode == $sc->id)
									echo "<option value='".$sc->id."' selected>";
								else
									echo "<option value='".$sc->id."'>";
								echo '[ '.MMSG_PREFIX.$sc->shortcode .' ]';
								echo "</option>";
							}
						}
					?>
					</select>
				</label>
			</p>
		<?php

//		$debug->filog(
//			'MMSG_widgets_control',
//			array('$options'		=> $options),
//			array('$_POST'			=> $_POST),
//			array('not $registered'	=> $registered),
//			array('widget_args'		=> $widget_args)
		);
	}

	// WIDGET DISPLAY
	function MMSG_sidebar_widget( $args, $widget_args = 1 )
	{
		require_once(dirname(__FILE__).'/lib/Debug.php');
		$debug = Debug::getInstance('_debug.log');

		if( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );

	    $widget_args	= wp_parse_args( $widget_args, array( 'number' => -1 ) );
		$widget_number	= $widget_args['number'];
		$options		= get_option(MMSG_MULTI_PREFIX);
		if( !is_array($options) )
		      $options = array();

		if (isset($options[$widget_number]))
		{
			$shortcodeID     = $options[$widget_number]['shortcode'];
			$generated_value = stripslashes( $this->get_shortcode($shortcodeID)->value );

			echo $before_widget;

			echo $before_title . $widget_args[$widget_number]['title'] . $after_title;

			echo "<div class='MMSG_shortcode'>\n";
			echo "\n<!-- ID:$shortcodeID -->\n";
			echo do_shortcode($generated_value);
			echo "\n<!-- ID:$shortcodeID -->\n";
			echo "\n</div>";

			echo $after_widget;
		}	

		$debug->filog('MMSG_sidebar_widget', $options, array('args' => $args), array('widget_args' => $widget_args));
	}

	// returns an HTML id for the widget's field
	function get_widget_field_id($number, $field_name)
	{
		return 'widget-'.MMSG_MULTI_PREFIX.'-'.$number.'-'.$field_name;
	}

	// returns an HTML name for the widget's field
	function get_widget_field_name($number, $field_name)
	{
		return 'widget-'.MMSG_MULTI_PREFIX.'['.$number.']['.$field_name.']';
	}

	// -------------------------------------------------------------------------------------

	// FILTERING WP QUERY VARIABLES
	function MMSG_query_vars( $q_vars )
	{
//		print "\n<!--\nMMSG_query_vars argument \$q_vars:\n";
//		print_r($q_vars);
//		print "\n-->\n";

		return $q_vars;
	}

	// FILTERING WP QUERY
	function MMSG_query( $q )
	{
//		print "\n<!--\nMMSG_query argument \$q:\n";
//		print_r($q);
//		print "\n-->\n";

		return $q;
	}

*/
}

?>