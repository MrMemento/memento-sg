<?php
/*
---------------------------------------------------------------------------

Plugin Name: Memento Design (MMD)
Version: 0.2
Description: All WordPress hacks used by Memento Media
Author: Barnabás Bucsy
Author URI: http://www.mementomedia.net

---------------------------------------------------------------------------

Copyright 2010 by Barnabás Bucsy

This file is part of MMD.

MMD is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

MMD is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with MMSG. If not, see <http://www.gnu.org/licenses/>.

---------------------------------------------------------------------------
*/

global $MMD;
$MMD = new MementoDesign();

class MementoDesign
{
	static $user;

	function MementoDesign()
	{
		// MMSG settings
		define(MMD_PLUGIN_NAME,		'Memento-Design');
		define(MMD_VERSION,			'0.2');
		define(MMD_PLUGIN_PATH,		ABSPATH		. '/wp-content/plugins/' . MMD_PLUGIN_NAME);
		define(MMD_HOME,			get_option('home'));
		define(MMD_PLUGIN_URL,		MMD_HOME	. '/wp-content/plugins/' . MMD_PLUGIN_NAME);
		define(MMD_TRACKBACK_URL,	'http://facebook.mementomedia.net/services/trackback.php');
		define(MMD_PING_URL,		'http://facebook.mementomedia.net/services/ping.php');

		// localization
		load_plugin_textdomain(MMD_PLUGIN_NAME, '/wp-content/plugins/' . MMD_PLUGIN_NAME . '/_assets/lang/');

		// store needed args
		add_action('init',						array(&$this, 'MMD_init' ));

		// hide category descriptions
		add_filter('category_description', 		array(&$this, 'MMD_category_description' ));

		// default login visual
		add_action('login_head',				array(&$this, 'MMD_login_head'));
		add_filter('login_headerurl',			array(&$this, 'MMD_login_headerurl'));
		add_filter('login_headertitle',			array(&$this, 'MMD_login_headertitle'));

		// admin URL
//		add_filter('admin_url',					array(&$this, 'MMD_admin_url'));

		// admin visual
		add_action('admin_head',				array(&$this, 'MMD_admin_head'));
		add_filter('admin_title',				array(&$this, 'MMD_admin_title'));
		add_action('admin_notices',				array(&$this, 'MMD_admin_notices'), 1);
		add_filter('admin_footer_text',			array(&$this, 'MMD_admin_footer_text'));
		add_action('admin_menu',				array(&$this, 'MMD_admin_menu' ));
	    add_filter('menu_order',				array(&$this, 'MMD_custom_menu_order'));
	    add_filter('custom_menu_order',			array(&$this, 'MMD_custom_menu_order'));
		add_action('wp_dashboard_setup',		array(&$this, 'MMD_wp_dashboard_setup'));
		add_filter('contextual_help',			array(&$this, 'MMD_contextual_help') );
//		add_filter('contextual_help_link',		array(&$this, 'MMD_contextual_help_link') );

		// widgets
		add_action('plugins_loaded',			array(&$this, 'MMD_plugins_loaded'));

		// authors' links in comments (nofollow, if needed)
		add_filter('get_comment_author_link',	array(&$this, 'MMD_get_comment_author_link'));

		// hide administrator(s) from lower roles
		add_filter('editable_roles',			array(&$this, 'MMD_editable_roles'));
/*{
		-----------
		MOD NEEDED
		-----------

		For this to work, one will need to modify the following file:
		"wp-admin/includes/user.php" line 666:
			$this->query_from_where = apply_filters('wp_user_search_query', $this->query_from_where." $search_sql");
}*/
		add_filter('wp_user_search_query',		array(&$this, 'MMD_wp_user_search_query'));
/*{
		-----------
		MOD NEEDED
		-----------

		For this to work, one will need to modify the following file:
		"wp-includes/user.php" line 282:
			return apply_filters('get_users_of_blog_result', $users);
}*/
		add_filter('get_users_of_blog_result',	array(&$this, 'MMD_get_users_of_blog_result'));

		// add extra memento trackback
		add_action('save_post',					array(&$this, 'MMD_save_post'));

		// customize generic ping(s)
		add_action('pre_ping',					array(&$this, 'MMD_pre_ping'));
/*{
		-----------
		MOD NEEDED
		-----------

		For this to work, one will need to modify "weblog_ping" function
			found in:	"wp-includes/comment.php" (wp cersion 2.9.2)
			line:		1758

		function weblog_ping($server = '', $path = '') {
			global $wp_version;
			include_once(ABSPATH . WPINC . '/class-IXR.php');

			// using a timeout of 3 seconds should be enough to cover slow servers
			$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
			$client->timeout = 3;
			$client->useragent = 'Memento-Media MLRPC Client';

			// when set to true, this outputs debug messages by itself
			$client->debug = false;
			$home = trailingslashit( get_option('home') );
			$args = apply_filters(
						'weblog_ping_query_args',
						array(
							get_option('blogname'),
							$home,
							get_bloginfo('rss2_url')
						),
						$server
					);

			array_unshift($args, 'weblogUpdates.extendedPing');
			if ( !call_user_func_array(array($client, 'query'), $args) )
			{
				// then try a normal ping
				$args[0] = 'weblogUpdates.ping';
				call_user_func_array(array($client, 'query'), $args);
			}
		}
}*/
		add_filter('weblog_ping_query_args',			array(&$this, 'MMD_weblog_ping_query_args'));
//		add_filter('wp_list_categories',				array(&$this, 'MMD_list_categories'));

		add_filter( 'wp_mail_from',						array(&$this, 'MMD_mail_from') );
		add_filter( 'wp_mail_from_name',				array(&$this, 'MMD_mail_from_name') );

		add_filter('excerpt_length',					array(&$this, 'MMD_excerpt_length') );

		// both needed!!!
		add_filter('wpsc_mobile_scripts_css_filters',	array(&$this, 'MMD_disable_user_script_and_css') );
		add_filter('wpsc_enqueue_user_script_and_css',	array(&$this, 'MMD_custom_user_script_and_css') );

//		add_filter('the_posts',							array(&$this, 'MMD_the_posts'));

		add_filter('posts_request',						array(&$this, 'MMD_posts_request'));
	}

	function MMD_posts_request($req)
	{
		
		global $wp_query;
		if ( !$wp_query->is_admin && !$wp_query->is_single )
		{
			if ( is_category() /*|| is_tag() || strstr($c_query,'taxonomy.term_id IN ')*/ )
			{
				$stickies = get_option('sticky_posts');
				if (!empty($stickies))
				{
					$req = preg_replace('/FROM/', ', IF(ID IN('.implode(', ', $stickies).'), 1, 0) AS memento_stick FROM', $req);
					$req = preg_replace('/ORDER BY/', 'ORDER BY memento_stick DESC,', $req);
				}
			}
		}

		return $req;
	}

	function MMD_disable_user_script_and_css()
	{
		return true;
	}

	function MMD_custom_user_script_and_css()
	{
		global $wp_styles, $wpsc_theme_url, $wpsc_theme_path;

		wp_enqueue_script( 'jQuery');
		wp_enqueue_script('wp-e-commerce',				WPSC_URL.'/js/wp-e-commerce.js',					array('jquery'));
		wp_enqueue_script('wp-e-commerce-ajax-legacy',	WPSC_URL.'/js/ajax.js');
		wp_enqueue_script('wp-e-commerce-dynamic',		$siteurl."/index.php?wpsc_user_dynamic_js=true");
		wp_enqueue_script('livequery',					WPSC_URL.'/wpsc-admin/js/jquery.livequery.js',		array('jquery'));
		wp_enqueue_script('jquery-rating',				WPSC_URL.'/js/jquery.rating.js',					array('jquery'));
		wp_enqueue_script('wp-e-commerce-legacy',		WPSC_URL.'/js/user.js',								array('jquery'));
//		wp_enqueue_script('wpsc-thickbox',				WPSC_URL.'/js/thickbox.js', array('jquery'), 'Instinct_e-commerce');

		if(file_exists($wpsc_theme_path.get_option('wpsc_selected_theme')."/".get_option('wpsc_selected_theme').".css")) {
			$theme_url = $wpsc_theme_url.get_option('wpsc_selected_theme')."/".get_option('wpsc_selected_theme').".css";
		} else {
			$theme_url = $wpsc_theme_url. '/default/default.css';
		}

		wp_enqueue_style( 'wpsc-theme-css',					$theme_url,																false, false, 'all');
		wp_enqueue_style( 'wpsc-theme-css-compatibility',	WPSC_URL. '/themes/compatibility.css',									false, false, 'all');
		wp_enqueue_style( 'wpsc-product-rater',				WPSC_URL.'/js/product_rater.css',										false, false, 'all');
//		wp_enqueue_style( 'wp-e-commerce-dynamic',			$siteurl."/index.php?wpsc_user_dynamic_css=true&category=$category_id",	false, false, 'all' );
//		wp_enqueue_style( 'wpsc-thickbox',					WPSC_URL.'/js/thickbox.css', 											false, false, 'all');
	}

	function MMD_excerpt_length($length) {
		return 25;
	}

	function MMD_init()
	{
		global $current_user;
		get_currentuserinfo();
		MementoDesign::$user = $current_user;
	}

	function MMD_disable()
	{
		return false;
	}

	function MMD_enable()
	{
		return true;
	}

	function MMD_category_description()
	{
		return false;
	}

	// -----------------------------------------------
	//  LOGIN PAGE
	// -----------------------------------------------

	function MMD_login_head()
	{
		// insert custom login stylesheet
		echo '<link rel="stylesheet" type="text/css" href="' . MMD_PLUGIN_URL . '/_assets/css/memento_login.css" />';
	}

	function MMD_login_headerurl()
	{
		// overwrite login logo's target URL
		echo 'http://www.mementomedia.net';
	}

	function MMD_login_headertitle()
	{
		// overwrite login logo's alternate text
	    echo 'Powered by Memento-Media';
	}

	// -----------------------------------------------
	//  ADMIN HELP
	// -----------------------------------------------
/*
	function MMD_contextual_help_link()
	{
		echo 'Memento contextual help link';
	}
*/
	function MMD_contextual_help()
	{
		// overwrite help texts in admin
		echo 'Memento Contextual Help';
	}

	// -----------------------------------------------
	//  ADMIN - GENERAL PAGES
	// -----------------------------------------------

	function MMD_admin_title($title)
	{
		// overwrite title of admin pages
		return preg_replace('/&#8212; wordpress/i', '- [ Memento-Media WebAdmin ]', $title);
	}

	function MMD_admin_head()
	{
		// insert custom admin stylesheet
		echo '<link rel="stylesheet" type="text/css" href="' . MMD_PLUGIN_URL . '/_assets/css/memento_admin.css" />';
	}

	function MMD_admin_notices()
	{
		if (!MementoDesign::$user->wp_capabilities['administrator'])
			remove_action( 'admin_notices', 'update_nag', 3 );
	}

	function MMD_admin_footer_text()
	{
		// overwrite footer of admin pages
		echo 'Powered by: <a href="http://www.mementomedia.net" alt="Memento-Media">Memento-Media</a>';
	}

	// -----------------------------------------------
	//  ADMIN - MENU
	// -----------------------------------------------

	function MMD_admin_url($url, $path = '')
	{
//		$this->filog($url, preg_replace('|wp-admin|', 'memento-admin', $url));
		return preg_replace('|wp-admin|', 'memento-admin', $url);
	}

	function MMD_custom_menu_order($menu_ord)
	{
		if (!MementoDesign::$user->wp_capabilities['administrator'])
		{
			// replace tools menu with separator
			global $menu;
			$menu[75] = array(
				'',
	            'read',
				'separator3',
				'',
				'wp-menu-separator'
			);
		}

		if (!$menu_ord)
			return true;

		// reorder menu elements
		return array(
			'index.php',			// dashboard
			'separator1',			// - spacer
			'edit-pages.php',		// pages
			'edit.php',				// posts
			'edit-comments.php',	// comments
			'separator2',			// - spacer
			'upload.php',			// media
			'link-manager.php',		// links
			'separator3',			// - spacer
			'profile.php',			// profile
			'separator-last'		// end?
		);
    }

	// -----------------------------------------------
	//  ADMIN - DASHBOARD
	// -----------------------------------------------

	function MMD_wp_dashboard_setup()
	{
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);

		if (!MementoDesign::$user->wp_capabilities['administrator'])
		{
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		}

	}

	// -----------------------------------------------
	//  ADMIN - EDIT PAGES
	// -----------------------------------------------

	function MMD_admin_menu()
	{
		if (!MementoDesign::$user->wp_capabilities['administrator'])
		{
			// EDIT POST: remove custom fields and trackback boxes
			remove_meta_box('postcustom',		'post', 'normal');
			remove_meta_box('trackbacksdiv',	'post', 'normal');

			// EDIT PAGE: remove custom fields and thumbnail
			remove_meta_box('postimagediv',		'page', 'normal');
			remove_meta_box('postcustom',		'page', 'normal');
		}

		if (!(
			MementoDesign::$user->wp_capabilities['administrator'] ||
			MementoDesign::$user->wp_capabilities['editor']
		))
		{
//			echo '<a href="' . wp_logout_url(get_bloginfo('url')) . '" title="Logout">' . __('Log out') . '</a>';
			wp_redirect(get_option('siteurl'));
			exit();
		}
			
	}

	// -----------------------------------------------
	//  ADMIN - USERS: Exclude admins
	// -----------------------------------------------

	// exclude admins and unverified users from user list on users page
	function MMD_wp_user_search_query($query)
	{
		// hide administrators in user list
		if (!MementoDesign::$user->wp_capabilities['administrator'])
		{
			if (preg_match('/WHERE 1=1/', $query)) // if no meta filter set
				$query = preg_replace("/WHERE 1=1/",
					"INNER JOIN wp_usermeta
						ON wp_users.ID = wp_usermeta.user_id
						WHERE wp_usermeta.meta_key = 'wp_capabilities'",
					$query
				);

			$query .= " AND NOT (wp_usermeta.meta_value LIKE '%administrator%')
						AND NOT EXISTS (
							SELECT user_id
								FROM wp_usermeta
								WHERE meta_key = 'email_verify_date'
									AND user_id = wp_users.ID
						)";
		}

//		$this->filog('MMD_wp_user_search_query', $query);
		return $query;
	}

	// hide administrators and unverified users
	// in role filter links and user counter on users page
	function MMD_get_users_of_blog_result($users)
	{
//		$this->filog('MMD_get_users_of_blog_result');
		if (!MementoDesign::$user->wp_capabilities['administrator'])
		{
			$unverified = array();
			global $register_plus;
			if ($register_plus)
			{
				global $wpdb;
				$unverified = $wpdb->get_row( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key='email_verify_date'", ARRAY_N );
			}

			$j		= count($users);
			$return	= array();
			for ($i=0; $i<$j; $i++)
			{
				if (
					!preg_match('/administrator/', $users[$i]->meta_value) &&
					!in_array($users[$i]->user_id, (array)$unverified)
				)
				{
					$return[] = $users[$i];
				}
			}
		}

		return $return;
	}

	// hide administrator role in manager
	function MMD_editable_roles($roles)
	{
//		$this->filog('MMD_editable_roles');
		if (!in_array('administrator', MementoDesign::$user->roles))
			unset($roles['administrator']);

		return $roles;
	}

	// -----------------------------------------------
	//  ADMIN - PING & TRACKBACK ON UPDATE
	// -----------------------------------------------

	// add our own server to trackback URLs
	// this way we will be updated with post URL, title, excerpt
	function MMD_save_post($postID)
	{
		global $wpdb;

		$newpost = $wpdb->get_row(
			"SELECT *
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = $postID
					AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				LIMIT 1"
		);

		if (!empty($newpost) && $newpost->post_status == 'publish')
		{
			// add our trackback URL to the list, but do not duplicate
			$pinged		= preg_replace('/\n\n+/', "\n", preg_replace('|'.MMD_TRACKBACK_URL.'|', '', $newpost->pinged));
			$to_ping	= preg_replace('/\n\n+/', "\n", preg_replace('|'.MMD_TRACKBACK_URL.'|', '', $newpost->to_ping));
			if (strlen($to_ping) > 0)
				$to_ping .= "\n";
			$to_ping .= MMD_TRACKBACK_URL;

			$wpdb->update(
				$wpdb->posts,
				array('to_ping' => MMD_TRACKBACK_URL,	'pinged' => ''),	array('ID' => $postID),
				array('%s', 							'%s'),				array('%d')
			);

			@do_trackbacks($postID);

			$wpdb->update(
				$wpdb->posts,
				array('to_ping' => $newpost->to_ping,	'pinged' => $newpost->pinged),	array('ID' => $postID),
				array('%s', 							'%s'),							array('%d')
			);
		}
	}

	// pings only send the site URL and feed URL
	function MMD_weblog_ping_query_args($args)
	{
		if (preg_match(MMD_PING_URL, $args['server']))
			$args['args'][] = 'Pinging my own server... :]';

		$args['args'][] = 'Powered by: Memento-Media';
		return $args;
	}

	// links of the pings
	function MMD_pre_ping(&$links)
	{
		foreach ($links as $l => $link)
		{
			// no self pinging
			if (!strpos( $link, MMD_HOME ) )
				unset($links[$l]);
		}

		return $links;
	}

	// -----------------------------------------------
	//  COMMENTS: Author links
	// -----------------------------------------------

	function MMD_get_comment_author_link($link)
	{
		global $comment;

		$author = get_comment_author();

		if ($comment->comment_author_url != '')
			$url  = get_comment_author_url();
		else
			return $author;

		$return = "<a href='$url' rel='external nofollow' class='url'>$author</a>";

		if (stripos($url, MMD_HOME) === 0)
			$return = "<a href='$url' class='url'>$author</a>";

		return $return;
	}

	// -----------------------------------------------
	//  EMAIL
	// -----------------------------------------------

	// new name
	function MMD_mail_from_name()
	{
		$name = 'Mr.Memento';
		// alternative the name of the blog
		// $name = get_option('blogname');
		$name = esc_attr($name);
		return $name;
	}

	// new email-adress
	function MMD_mail_from()
	{
		$email = 'barnabas.bucsy@mememntomedia.net';
		$email = is_email($email);
		return $email;
	}

	// -----------------------------------------------
	//  WIDGETS - INIT
	// -----------------------------------------------

	function MMD_plugins_loaded()
	{
		register_sidebar_widget(__('Registration Form', MMD_PLUGIN_NAME), array(&$this, 'registration_widget'));
		register_widget_control(__('Registration Form', MMD_PLUGIN_NAME), array(&$this, 'registration_widget_control'));
	}

	function MMD_list_categories($categories)
	{
//		$this->filog($categories);
		return $categories;
	}

	// -----------------------------------------------
	//  REGISTRATION WIDGET
	// -----------------------------------------------

	function registration_widget_control()
	{
		$options = $newoptions = get_option('widget_registration');

		if ( isset($_POST["registration-submit"]) )
			$newoptions['title'] = strip_tags(stripslashes($_POST["registration-title"]));

		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_registration', $options);
		}

		$title = attribute_escape($options['title']);
		?>
			<p>
				<label for="registration-title">
					<?php _e('Title:'); ?> <input class="widefat" id="registration-title" name="registration-title" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>
			<input type="hidden" id="registration-submit" name="registration-submit" value="1" />
		<?php
	}

	function registration_widget($args)
	{

		global $pagenow;

		if ($_GET['action'] != 'register')
		{
			extract($args);

			$options	= get_option('widget_registration');
			$title		= empty($options['title']) ? __('Registration', MMD_PLUGIN_NAME) : apply_filters('widget_title', $options['title']);

			echo $before_widget;
			echo $before_title . $title . $after_title;
			$this->MMD_registration_form($args);
			echo $after_widget;
		}
	}

	// -----------------------------------------------
	//  REGISTRATION FORM
	// -----------------------------------------------

	function MMD_registration_form($args = null)
	{
		$cap_id				= rand(0, 15600);
		$register_button	= isset($args['register_button'])	? $args['register_button']	: __('Register', MMD_PLUGIN_NAME);
		$email				= isset($args['email'])				? $args['email']			: __('E-Mail', MMD_PLUGIN_NAME);
		$user_name			= isset($args['user_name'])			? $args['user_name']		: __('User Name', MMD_PLUGIN_NAME);
		$user_login			= isset($args['user_login'])		? $args['user_login']		: __('E-mail or username', MMD_PLUGIN_NAME);

		?>
		<div id="register_form">
			<h2><?= __('Registration', MMD_PLUGIN_NAME); ?></h2>
			<form class="registerform" name="registerform" id="registerform" action="?action=newuser" method="post">
				<p>
					<label for="user_name" class="user_name"><?= __('User Name', MMD_PLUGIN_NAME); ?>:</label><br />
					<input type="text" name="user_name" id="user_name" class="input" value="" size="20" tabindex="10" />
					<br />
					<label for="user_email" class="user_email"><?= __('E-Mail', MMD_PLUGIN_NAME); ?>:</label><br />
					<input  type="text" name="user_email" id="user_email" class="input" value="" size="25" tabindex="20" />
					<br />
				</p>
				<?php
					do_action('register_form');
				?>
				<p>
					<img class="captcha" src="<?= MMD_PLUGIN_URL; ?>/_assets/captcha/3DCaptcha.php?id=<?= $cap_id; ?>" />
				</p>
				<p>
					<label for="captcha"><?= __('Please enter the letters shown', MMD_PLUGIN_NAME); ?>:</label>
					<br/>
					<input tabindex="60" name="captcha" type="text">
					<input type="hidden" name="cap_id" value="<?= $cap_id; ?>">
				</p>
				<p id="reg_passmail">
					<?php _e('A password will be e-mailed to you.') ?>
				</p>
				<p class="submit">
					<input tabindex="70" type="submit" name="wp-submit" id="wp-submit" value="<?= $register_button; ?>" />
				</p>
			</form>
		</div>

		<?php

	    $message = __('Please enter your username or e-mail address. You will receive a new password via e-mail.', MMD_PLUGIN_NAME);

	    ?>
		<div id="lostpass_form">
			<h2><?= __('Retrieve password', MMD_PLUGIN_NAME); ?></h2>
		    <form name="lostpasswordform" class="lostpasswordform" action="?action=lostpassword" method="post">
		        <p>
		            <label for="user_login"><?php _e('E-mail or username', MMD_PLUGIN_NAME) ?>:</label>
					<br/>
		            <input type="text" name="user_login" id="user_login" class="input" value="" size="20" />
		        </p>
		        <p class="submit">
		            <input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Get New Password', MMD_PLUGIN_NAME); ?>" />
		        </p>
		    </form>
		</div>
	    <?php
	}

	function MMD_user_handler()
	{
		// LOGGED IN

		if ( is_user_logged_in() )
		{
			echo '<p class="already-registered">'.__("You are already registered for this site.", MMD_PLUGIN_NAME)."</p>\n";
			return;
		}

		global $register_plus;
		if ($register_plus)
			$register_plus->ValidateUser();

		$user_data = array(
			'user_name'		=> sanitize_user($_POST['user_name'], true),
			'email'			=> $_POST['user_email'],
			'user_login'	=> $_POST['user_login']
		);

		if ($_GET['action'] == 'newuser' )
		{
			$valid = true;

			// USER NAME

			if ($user_data['user_name'] != $_POST['user_name'] || strlen($user_data['user_name']) < 4 || $user_data['user_name'] == __('User Name', MMD_PLUGIN_NAME))
			{
				$valid = false;
				echo '<p class="reg-error">'.sprintf(__("User name must be at least %d characters long and may not contain any special characters", MMD_PLUGIN_NAME), 5)."</p>\n";
			}

			// EMAIL

	//		print '<p><strong>debug:</strong> $user_email : '.$_POST['user_email'].'</p>';
			if (!$this->validate_email($user_data['email']))
			{
				$valid = false;
				echo '<p class="reg-error">'.__("Please enter valid e-mail address", MMD_PLUGIN_NAME)."</p>\n";
			}

			// NO CAPTCHA

			if (!isset($_POST['captcha']))
			{
				$valid = false;
				echo '<p class="reg-error">'.__("Please enter the letters found in the picture", MMD_PLUGIN_NAME)."</p>\n";
			}

			if (!$valid)
			{
				$this->MMD_registration_form($user_data);
				return;
			}

			// CHECK CAPTCHA

			$cTexts			= preg_split('/\n/', file_get_contents(MMD_PLUGIN_PATH.'/_assets/captcha/words.txt'), -1, PREG_SPLIT_NO_EMPTY);
			$captchaText	= strtoupper($cTexts[$_POST['cap_id']]);

			if ($captchaText != strtoupper($_POST['captcha']))
			{
				echo '<p class="reg-error">'.__("Captcha invalid, please try again", MMD_PLUGIN_NAME)."</p>\n";
				$this->MMD_registration_form($user_data);
				return;
			}

			require_once(ABSPATH . WPINC . '/registration.php' );
			require_once(ABSPATH . WPINC . '/pluggable.php' );

			// CHECK USERNAME

			$user_test = validate_username($user_data['user_name']);
//			print '<p><strong>debug:</strong> $user_test : '.$user_test.'</p>';
			if ($user_test != true)
			{
				echo '<p class="reg-error">'.__('Invalid username', MMD_PLUGIN_NAME)."</p>\n";
				$this->MMD_registration_form($user_data);
				return;
			}

			$user_id = username_exists( $user_data['user_name'] );
//			print '<p><strong>debug:</strong> $user_id : '.$user_id.'</p>';
			if ($user_id)							
			{
				echo '<p class="reg-error">'.__('An account with this username has already been registered', MMD_PLUGIN_NAME)."</p>\n";
				$this->MMD_registration_form($user_data);
				return;
			}

			// CHECK EMAIL

			$email_test = email_exists($user_data['email']);
//			print '<p><strong>debug:</strong> $email_test : '.$email_test.'</p>';
			if ($email_test != false)
			{
				echo '<p class="reg-error">'.__('An account with this email has already been registered', MMD_PLUGIN_NAME)."</p>\n";
				$this->MMD_registration_form($user_data);
				return;
			}

			//------------------
			// GENERATE ACCOUNT
			//------------------

			$random_password	= wp_generate_password( 10, false );
			$user_id			= wp_create_user( $user_data['user_name'], $random_password, $user_data['email'] );

			//add flag for the user to change their auto-generated password
			update_user_option($user_id, 'default_password_nag', true, true);

			//notify admin of new user
			wp_new_user_notification($user_id, $random_pass);
/*
			//create user confirmation message and send email
			$emessage = sprintf(__("Thank you  for signing up on %s. Here is your password. You should longin and change it as soon as possible.", MMD_PLUGIN_NAME), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES))."\r\n\r\n".
						__("Username", MMD_PLUGIN_NAME).": {$user_data['user_name']}\r\n".
						__("Password", MMD_PLUGIN_NAME).": $random_password\r\n".
						__("Login", MMD_PLUGIN_NAME).": ".MMD_HOME;

		    if (!wp_mail(
				$user_data['email'],
				sprintf(__('Registration on %s', MMD_PLUGIN_NAME), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)),
				$emessage
			))
				die('<p>' . __('The e-mail could not be sent.', MMD_PLUGIN_NAME) . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', MMD_PLUGIN_NAME) . '</p>');
*/
			echo '<p class="reg-success">'.
				__('Registration successful.', MMD_PLUGIN_NAME).
				__('A password was sent to you via email.', MMD_PLUGIN_NAME).
				"</p>\n";
		}
		else if ($_GET['action'] == 'lostpassword')
		{
			if (strlen($user_data['user_login']) > 4 && $user_data['user_login'] != __('E-mail or username', MMD_PLUGIN_NAME))
			{
				if (preg_match('/@/', $user_data['user_login']))
					$where = "WHERE user_email = '".$user_data['user_login']."'";
				else
					$where = "WHERE user_login = '".$user_data['user_login']."'";

				global $wpdb;
				$curr_user = $wpdb->get_row($wpdb->prepare(
									"SELECT *
										FROM {$wpdb->users}
										$where"
								));
				if (empty($curr_user))
				{
					echo '<p class="reg-error">'.__("User name or email invalid", MMD_PLUGIN_NAME)."</p>\n";
					$this->MMD_registration_form($user_data);
					return;
				}

				//-----------------
				// CREATE PASSWORD
				//-----------------

				$new_pass = wp_generate_password();
				do_action('password_reset', $curr_user, $new_pass);
				wp_set_password($new_pass, $curr_user->ID);
				//Set up the Password change nag
				update_usermeta($curr_user->ID, 'default_password_nag', true);

				
				$emessage = sprintf(__("Thank you  for signing up on %s. Here is your password. You should longin and change it as soon as possible.", MMD_PLUGIN_NAME), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES))."\r\n\r\n".
							__("Username", MMD_PLUGIN_NAME).": {$curr_user->user_login}\r\n".
							__("Password", MMD_PLUGIN_NAME).": $new_pass\r\n".
							__("Login", MMD_PLUGIN_NAME).": ".MMD_HOME;

			    if ( !wp_mail(
					$curr_user->user_email,
					sprintf(__('Password reset on %s', MMD_PLUGIN_NAME), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)),
					$emessage
				))
			          die('<p>' . __('The e-mail could not be sent.', MMD_PLUGIN_NAME) . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', MMD_PLUGIN_NAME) . '</p>');

			    wp_password_change_notification($curr_user);

				echo '<p class="pass-reset-success">'.__('A password was sent to you via email.', MMD_PLUGIN_NAME)."</p>\n";
			}
			else
			{
				echo '<p class="reg-error">'.__("E-mail or username invalid, please try again", MMD_PLUGIN_NAME)."</p>\n";
				$this->MMD_registration_form($user_data);
				return;
			}
		}
		else
		{
			$this->MMD_registration_form();
			return;
		}
	}

	// -----------------------------------------------
	//  HELPERS
	// -----------------------------------------------

	function validate_email($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex)
		{
			$isValid = false;
		}
		else
		{
			$domain		= substr($email, $atIndex+1);
			$local		= substr($email, 0, $atIndex);
			$localLen	= strlen($local);
			$domainLen	= strlen($domain);

			if ($localLen < 1 || $localLen > 64)
			{
				// local part length exceeded
				$isValid = false;
			}
			else if ($domainLen < 1 || $domainLen > 255)
			{
				// domain part length exceeded
				$isValid = false;
			}
			else if ($local[0] == '.' || $local[$localLen-1] == '.')
			{
				// local part starts or ends with '.'
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $local))
			{
				// local part has two consecutive dots
				$isValid = false;
			}
			else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
			{
				// character not valid in domain part
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $domain))
			{
				// domain part has two consecutive dots
				$isValid = false;
			}
			else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
			{
				// character not valid in local part unless 
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local)))
				{
					$isValid = false;
				}
			}

			if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
			{
			   // domain not found in DNS
			   $isValid = false;
			}
		}

		return $isValid;
	}

	function filog()
	{
		$args = func_get_args();
		if (count($args) == 1 && $args[0])
			$args = $args[0];

		file_put_contents(
			'/home/vesper/public_html/hu/wp-content/plugins/' . MMD_PLUGIN_NAME . '/mm.log',
			'Logged on: '.date(DATE_RFC822)."\r\nArguments: ".print_r($args, true)."\r\n",
			FILE_APPEND
		);
	}
}

?>