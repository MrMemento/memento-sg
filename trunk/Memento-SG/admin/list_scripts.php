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
	require(dirname(__FILE__) . '/edit_script.php');
}
else
{
	global $MMSG;
	global $wpdb;

	if ($_GET['action'] == 'remove')
	{
		$MMSG->remove_script(intval($_GET['id']));
		$MMSG->fade_msg(__('Script removed.', MMSG_PLUGIN_NAME));
	}

	if (!isset(MementoShortcodeGenerator::$scripts) || $_GET['action'] == 'remove')
		MementoShortcodeGenerator::$scripts = $MMSG->list_scripts();

	/* START BIG ELSE STATEMENT */
?>
	<script>
	//<![CDATA[
		function confirm_delete() {
			answer = confirm("<?php _e('Are you sure you want to delete this script?', MMSG_PLUGIN_NAME);?>");
			if (answer)
				return true;
			else
				return false;
		}
		function add_script() {
			var typeval = document.getElementById('type_to_add').value;
			switch (typeval) {
			 	case 'dependent':
					document.location = "<?= MMSG_ADMIN_URL ?>add_new_script_dependent.php";
					return false;
				break;
		 		case 'global':
					document.location = "<?= MMSG_ADMIN_URL ?>add_new_script_global.php";
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
		<h2>Memento Media Shortcode Generator (MMSG) - Script Manager</h2>
		<p>
			<?php echo sprintf(__('You have <b>%s</b> script(s) defined in table: <b>%s</b>.', MMSG_PLUGIN_NAME), MementoShortcodeGenerator::$scripts['num_scripts'], $wpdb->prefix.MMSG_PREFIX.'scripts'); ?>
		</p>
		<form action="<?= MMSG_ADMIN_URL ?>list_scripts.php" method="post">
			<div class="tablenav" style="width:700px;">
				<p class="tablenav-pages">
					<select id="type_to_add">
						<option value="0">
							<? _e('Select type of script to add', MMSG_PLUGIN_NAME); ?> &nbsp; &nbsp;
						</option>
						<option value="global">
							<? _e('Global Script', MMSG_PLUGIN_NAME); ?>
						</option>
						<option value="dependent">
							<? _e('Dependent Script', MMSG_PLUGIN_NAME); ?>
						</option>
					</select>
					<span class="submit">
						<button onclick="return add_script();">
							<? _e('Add', MMSG_PLUGIN_NAME); ?>
						</button> &nbsp; &nbsp;
					</span>
					<?php
						$max_per_page  = 30;
						$total_scripts = MementoShortcodeGenerator::$scripts['num_scripts'];

						if ($total_scripts > $max_per_page)
						{
							$current_page = intval($_GET['pg']);
							$scripts = array_slice(MementoShortcodeGenerator::$scripts, $current_page*$max_per_page, $max_per_page);

							$pages = ceil($total_scripts / $max_per_page);
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
								<a href="<?= add_query_arg('pg', $i) ?>" class="page-numbers">
									<?= $i+1; ?>
								</a> &nbsp;
							<?php
								}
							}
						}
						else
						{
							$scripts = MementoShortcodeGenerator::$scripts;
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
						<? _e('name', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('source', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('script dependencies', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('version', MMSG_PLUGIN_NAME); ?>
					</th>
					<th>
						<? _e('place', MMSG_PLUGIN_NAME); ?>
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
					if (is_array($scripts) && !empty($scripts))
					{
						$cnt=0;

						foreach($scripts as $mmscr)
						{
							// !!! else it could be associative string value like the regex search pattern !!!
							if (is_object($mmscr))
							{
							?>
								<tr class="<?php if ($cnt %2 == 0) echo "alternate"; ?>">
									<td>
										<?php echo $mmscr->id ?>
									</td>
									<td>
										<a href="<?= add_query_arg(array('id'=>$mmscr->id,'action'=>'edit'), MMSG_ADMIN_URL . "list_scripts.php")?>"><?php echo $mmscr->name; ?></a>
									</td>
									<td>
										<?php echo substr(strip_tags(stripslashes($mmscr->source)), 0, 50) . (strlen($mmscr->source) > 50 ? " [...]" : ''); ?>
									</td>
									<td>
										<?php echo substr(strip_tags(stripslashes($mmscr->dependencies)),0,50) . (strlen($mmscr->dependencies) > 50 ? " [...]" : ''); ?>
									</td>
									<td>
										<?php echo $mmscr->version; ?>
									</td>
									<td>
										<?php $mmscr->foot == 1 ? _e('foot', MMSG_PLUGIN_NAME) : _e('head', MMSG_PLUGIN_NAME); ?>
									</td>
									<td>
										<?php echo $mmscr->type ?>
									</td>
									<td>
										<?php echo $mmscr->codes; ?>
									</td>
									<td>
										[<a href="<?= add_query_arg(array('id'=>$mmscr->id,'action'=>'edit'), MMSG_ADMIN_URL . "list_scripts.php")?>"><? _e('edit', MMSG_PLUGIN_NAME); ?></a>] 
										[<a href="<?= add_query_arg(array('id'=>$mmscr->id,'action'=>'remove')); ?>" onclick="return confirm_delete();"><? _e('remove', MMSG_PLUGIN_NAME); ?></a>]
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
						<? _e('There are no scripts defined to be included.', MMSG_PLUGIN_NAME); ?>
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