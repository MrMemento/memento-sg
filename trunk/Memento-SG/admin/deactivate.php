<?php

/*
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

?>
<div class="wrap">
	<h2>MMSG <?= __('deactivate options', MMSG_PLUGIN_NAME); ?></h2>
<?php

	if ($_GET['action'] == 'deactivate' || $_GET['action'] == 'deactivate_and_delete')
	{
		if (!check_admin_referer('MMSG_deactivate', 'wp_nonce'))
			return false;

		$res = '';

		if ($_GET['action'] == 'deactivate')
		{
			deactivate_plugins(MMSG_PLUGIN_NAME.'/'.MMSG_PLUGIN_NAME.'.php');
			delete_option('MMSG_version');

			$res .= '<div class="updated fade"><p>'.
				__('Plugin deactivated!', MMSG_PLUGIN_NAME).
				"</p></div>\n";
		}
		else if ($_GET['action'] == 'deactivate_and_delete')
		{
			deactivate_plugins(MMSG_PLUGIN_NAME.'/'.MMSG_PLUGIN_NAME.'.php');
			delete_option('MMSG_version');
			delete_option('MMSG_DB_version');

			global $wpdb;

			$wpdb->query("DROP TABLE ".$wpdb->prefix.MMSG_PREFIX.'shortcodes');
			$wpdb->query("DROP TABLE ".$wpdb->prefix.MMSG_PREFIX.'scripts');
			$wpdb->query("DROP TABLE ".$wpdb->prefix.MMSG_PREFIX.'styles');

			$res .= '<div class="updated fade"><p>'.
				__('Plugin deactivated, database tables dropped!', MMSG_PLUGIN_NAME).
				"</p></div>\n";
		}

		$res .= '</div><!--wrap-->';
		echo $res;
	}
	else
	{
?>
	<br/>
	<form method="get" action="<?= $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="page" value="<?= $_GET['page']; ?>"/>
		<input type="hidden" name="deactivate" value="true"/>
		<input type="hidden" name="action" value="deactivate"/>
		<input type="hidden" name="wp_nonce" value="<?= wp_create_nonce('MMSG_deactivate'); ?>"/>
		<input type="submit" value="<?= __('Deactivate plugin', MMSG_PLUGIN_NAME); ?>" class="button-secondary" /><br/><br/>
	</form>
	<form method="get" action="<?= $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="page" value="<?= $_GET['page']; ?>"/>
		<input type="hidden" name="deactivate" value="true"/>
		<input type="hidden" name="action" value="deactivate_and_delete"/>
		<input type="hidden" name="wp_nonce" value="<?= wp_create_nonce('MMSG_deactivate'); ?>"/>
		<input type="submit" value="<?= __('Deactivate plugin and delete tables from database', MMSG_PLUGIN_NAME); ?>" class="button-secondary" />
	</form>
</div><!--wrap-->
	<?php		
}
?>