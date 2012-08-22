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

if ($_POST || $_GET['action'] == 'edit')
{
	require(dirname(__FILE__) . '/edit_code.php');
}
else
{
	global $MMSG;
	global $wpdb;

	if ($_GET['action'] == 'remove')
	{
		$MMSG->remove_shortcode(intval($_GET['id']));
		$MMSG->fade_msg(__('Shortcode removed.', MMSG_PLUGIN_NAME));
	}

	if (!isset(MementoShortcodeGenerator::$shortcodes) || $_GET['action'] == 'remove')
		MementoShortcodeGenerator::$shortcodes = $MMSG->list_shortcodes();

	/* START BIG ELSE STATEMENT */
?>
	<script>
	//<![CDATA[
		function confirm_delete() {
			answer = confirm("<?php _e('Are you sure you want to delete this shortcode?', MMSG_PLUGIN_NAME);?>");
			if (answer)
				return true;
			else
				return false;
		}
		function add_shortcode() {
			var typeval = document.getElementById('type_to_add').value;
			switch (typeval) {
			 	case 'rich':
					document.location = "<?= MMSG_ADMIN_URL ?>add_new_code_rich.php";
					return false;
				break;
		 		case 'plain':
					document.location = "<?= MMSG_ADMIN_URL ?>add_new_code_plain.php";
					return false;
				break;
		 		case 'advanced':
					document.location = "<?= MMSG_ADMIN_URL ?>add_new_code_advanced.php";
					return false;
				break;
				default:
					return false;
				break;
			}
		}
	//]]>
	</script>
	<div class="wrap">
		<h2>Memento Media Shortcode Generator (MMSG)</h2>
		<p>
			<?php echo sprintf(__('You have <b>%s</b> shortcode(s) defined in table: <b>%s</b>.', MMSG_PLUGIN_NAME), MementoShortcodeGenerator::$shortcodes['num_codes'], $wpdb->prefix.MMSG_PREFIX.'shortcodes'); ?>
		</p>
		<form action="<?= MMSG_ADMIN_URL ?>list_codes.php" method="post">
			<div class="tablenav" style="width:700px;">
				<p class="tablenav-pages">
					<select id="type_to_add">
						<option value="0">
							<? _e('Select type of shortcode to add', MMSG_PLUGIN_NAME); ?> &nbsp; &nbsp;
						</option>
						<option value="rich">
							<? _e('Rich Content Shortcode', MMSG_PLUGIN_NAME); ?>
						</option>
						<option value="plain">
							<? _e('Plain Content Shortcode', MMSG_PLUGIN_NAME); ?>
						</option>
						<option value="advanced">
							<? _e('Advanced Content Shortcode', MMSG_PLUGIN_NAME); ?>
						</option>
					</select>
					<span class="submit">
						<button onclick="return add_shortcode();">
							<? _e('Add', MMSG_PLUGIN_NAME); ?>
						</button> &nbsp; &nbsp;
					</span>
					<?php
						$max_per_page = 30;
						$total_codes  = MementoShortcodeGenerator::$shortcodes['num_codes'];

						if ($total_codes > $max_per_page)
						{
							$current_page = intval($_GET['pg']);

							$codes = array_slice(MementoShortcodeGenerator::$shortcodes, $current_page*$max_per_page, $max_per_page);

							$pages = ceil($total_codes / $max_per_page);
							$dots = false;

							for($i=0; $i < $pages; $i++)
							{
								//if($dots){ continue;}
								if ($current_page == $i)
								{
									echo "<b>" . ($current_page+1) . "</b> &nbsp;";
								}
								else
								{
							?>
								<a href="<?=add_query_arg('pg',$i)?>" class="page-numbers">
									<?=$i+1;?>
								</a> &nbsp;
							<?php
								}
							}
						}
						else
						{
							$codes = MementoShortcodeGenerator::$shortcodes;
						}
					?>
				</p>
			</div>
			<br clear="all" />
			<table class="widefat" style="width:95%;">
				<tr>
					<th>
						<? _e('id', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('shortcode', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('text', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('type', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('code dependencies', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('actions', MMSG_PLUGIN_NAME); ?>
					</th>
				</tr>
				<?php
					if (is_array($codes) && !empty($codes))
					{
						$cnt=0;
			
						foreach($codes as $mmc)
						{
							// !!! else it could be associative string value like the regex search pattern !!!
							if (is_object($mmc))
							{
							?>
								<tr class="<?php if ($cnt %2 == 0) echo "alternate"; ?>">
									<td>
										<?php echo $mmc->id ?>
									</td>
									<td>
										[<a href="<?= add_query_arg(array('id'=>$mmc->id,'action'=>'edit','type'=>$mmc->type), MMSG_ADMIN_URL . "list_codes.php")?>"><?php echo MMSG_PREFIX . $mmc->shortcode; ?></a>]
									</td>
									<td>
										<?php echo substr(strip_tags(stripslashes($mmc->value)),0,50) . (strlen($mmc->value) > 50 ? " [...]" : ''); ?>
									</td>
									<td>
										<?php echo $mmc->type; ?>
									</td>
									<td>
										<?php echo substr(strip_tags(stripslashes($mmc->dependencies)),0,50) . (strlen($mmc->dependencies) > 50 ? " [...]" : ''); ?>
									</td>
									<td>
										[<a href="<?= add_query_arg(array('id'=>$mmc->id,'action'=>'edit','type'=>$mmc->type), MMSG_ADMIN_URL . "list_codes.php")?>"><? _e('edit', MMSG_PLUGIN_NAME); ?></a>] 
										[<a href="<?= add_query_arg(array('id'=>$mmc->id,'action'=>'remove')); ?>" onclick="return confirm_delete();"><? _e('remove', MMSG_PLUGIN_NAME); ?></a>]
									</td>
								</tr>
							<?php
							$cnt++;
						}
					}
				}
				else
				{
				?>
					<td colspan="5" class="empty">
						<? _e('There are no generated shortcodes defined.', MMSG_PLUGIN_NAME); ?>
					</td>
					<?php
				}
				?>
			</table>
		</form>
		<br /><br />
	</div>
	<?php
	/* END BIG ELSE STATEMENT */
}
?>