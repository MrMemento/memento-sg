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

	global $MMSG;

	if ($_POST)
	{
		$id           = $_POST['id'];
		$name         = $MMSG->clean_string($_POST['name']);
		$source       = $_POST['source'];
		$dependencies = $MMSG->clean_white($_POST['dependencies']);
		$version      = $_POST['version'];
		$foot         = $_POST['foot'];
		$type         = $_POST['type'];
		$codes        = $MMSG->clean_white($_POST['codes']);

		if (($_POST['action']) == 'new')
		{
			if (!empty($name) && !$MMSG->exists_script($name))
			{
				$id = $MMSG->add_script($name, $source, $dependencies, $version, $foot, $type, $codes);

				$action = 'update';

				$MMSG->fade_msg(sprintf(__('Script %s added.', MMSG_PLUGIN_NAME), $name));

				$script = $MMSG->get_script($id);
			}
			else
			{
				if (empty($name))
				{
					$MMSG->fade_msg(__('Sript name was empty, please enter a name!', MMSG_PLUGIN_NAME));
				}
				else
				{
					$MMSG->fade_msg(sprintf(__('Script with name %s already exists, please use another name, or edit the existing one!', MMSG_PLUGIN_NAME), $name));
				}

				$script               = new stdClass;
				$script->name         = $name;
				$script->source       = $source;
				$script->dependencies = $dependencies;
				$script->version      = $version;
				$script->foot         = $foot;
				$script->codes        = $codes;
			}
		}
		else
		{
			$MMSG->update_script($id, $name, $source, $dependencies, $version, $foot, $type, $codes);

			$MMSG->fade_msg(sprintf(__('Script %s updated.', MMSG_PLUGIN_NAME), $name));

			$script = $MMSG->get_script($id);
		}
	}
	else
	{
		$id = intval($_GET['id']);

		if ($id == 0)
		{
			$action       = 'new';
			$script       = new stdClass;
			$script->type = $type;
		}
		else
		{
			$action = "update";
			$script = $MMSG->get_script($id);
			$type   = $script->type;
		}
	}
?>
<div class="wrap">
	<h2>Memento Media Shortcode Generator (MMSG) - Script manager</h2>
	<p>
		<?php
			if ($id != 0)
			{
				_e('Here you can modify the JavaScript library to be added to your HTML frontend\'s head or foot. It uses <code>wp_enqueue_script</code> function, to avoid duplicate scripts, use versioning and dependencies, and to handle built in libs aswell.<br/>Dependencies should be a comma separated string WITHOUT whitespaces, this will be converted to array with <code>preg_split</code>.<br/><a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_script">Further reference</a>', MMSG_PLUGIN_NAME);
	 		}
			else
			{
				_e('Here you can add JavaScript library to your HTML frontend\'s head or foot. It uses <code>wp_enqueue_script</code> function, to avoid duplicate scripts, use versioning and dependencies, and to handle built in libs aswell.<br/>Dependencies should be a comma separated string WITHOUT whitespaces, this will be converted to array with <code>preg_split</code>.<br/><a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_script">Further reference</a>', MMSG_PLUGIN_NAME);
			}
		?>
	</p>
	<form action="<?= MMSG_ADMIN_URL ?>list_scripts.php" method="post">
		<input type="hidden" name="id" value="<?= $id ?>" />
		<input type="hidden" name="action" value="<?= $action ?>" />
		<table class="widefat" style="width:95%">
			<tr>
				<th>
					<? _e('name', MMSG_PLUGIN_NAME); ?>
				</th>
				<th>
					<? _e('source', MMSG_PLUGIN_NAME); ?>
				</th>
				<th>
					<? _e('version', MMSG_PLUGIN_NAME); ?>
				</th>
				<th>
					<? _e('place', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td>
					<input type="text" name="name" value="<?= $script->name ?>" style="width:100%;" />
				</td>
				<td>
					<input type="text" name="source" value="<?= stripslashes($script->source) ?>" style="width:100%;" />
				</td>
				<td>
					<input type="text" name="version" value="<?= $script->version ?>" style="width:100%;" />
				</td>
				<td>
					<select name="foot">
						<option value="0" <?= $script->foot == 0 ? 'selected' : '' ?>>
							<? _e('head', MMSG_PLUGIN_NAME); ?>
						</option>
						<option value="1" <?= $script->foot == 1 ? 'selected' : '' ?>>
							<? _e('foot', MMSG_PLUGIN_NAME); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>
		<?php
			if (!isset(MementoShortcodeGenerator::$scripts))
				MementoShortcodeGenerator::$scripts = $MMSG->list_scripts();

			$available_scripts = array();
			$dependent_scripts = array();
			foreach (MementoShortcodeGenerator::$scripts as $mmscr)
			{
				if (is_object($mmscr) && $mmscr->name != $script->name)
				{
					if (preg_match('/'.$mmscr->name.'/', $script->dependencies))
						$dependent_scripts[] = $mmscr->name;
					else
						$available_scripts[] = $mmscr->name;
				}
			}
		?>
		<br/>
		<div style="float: left;">
			<div class='dragdrop_panel'>
				<h2><? _e('Available Scripts', MMSG_PLUGIN_NAME); ?></h2>
				<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
					<a href='#' onclick='return jQuery.dds.selectAll("avail_scripts");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectNone("avail_scripts");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectInvert("avail_scripts");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
				</p>
				<div>
					<ul id="avail_scripts">
						<?php
							foreach ($available_scripts as $mmscr)
								print '<li id='.$mmscr.'>'.$mmscr.'</li>';
						?>
					</ul>
				</div>
			</div>
			<div class='dragdrop_panel'>
				<h2><? _e('Dependent Scripts', MMSG_PLUGIN_NAME); ?></h2>
				<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
					<a href='#' onclick='return jQuery.dds.selectAll("dep_scripts");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectNone("dep_scripts");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectInvert("dep_scripts");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
				</p>
				<div>
					<ul id="dep_scripts">
						<?php
							foreach ($dependent_scripts as $mmscr)
								print '<li id='.$mmscr.'>'.$mmscr.'</li>';
						?>
					</ul>
				</div>
			</div>	
		</div>
		<input type="hidden" name="type" value="<?= $type ?>" />
		<?php
			if ($type == 'dependent')
			{
				if (!isset(MementoShortcodeGenerator::$shortcodes))
					MementoShortcodeGenerator::$shortcodes = $MMSG->list_shortcodes();

				$available_codes = array();
				$dependent_codes = array();
				foreach (MementoShortcodeGenerator::$shortcodes as $mmsc)
				{
					if (is_object($mmsc))
					{
						if (preg_match('/'.$mmsc->shortcode.'/', $script->codes))
							$dependent_codes[] = $mmsc->shortcode;
						else
							$available_codes[] = $mmsc->shortcode;
					}
				}
		?>
			<br/>
			<div style="float: left;">
				<div class='dragdrop_panel'>
					<h2><? _e('Available Shortcodes', MMSG_PLUGIN_NAME); ?></h2>
					<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
						<a href='#' onclick='return jQuery.dds.selectAll("avail_codes");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectNone("avail_codes");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectInvert("avail_codes");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
					</p>
					<div>
						<ul id="avail_codes">
							<?php
								foreach ($available_codes as $mmsc)
									print '<li id='.$mmsc.'>'.MMSG_PREFIX.$mmsc.'</li>';
							?>
						</ul>
					</div>
				</div>
				<div class='dragdrop_panel'>
					<h2><? _e('Dependent Shortcodes', MMSG_PLUGIN_NAME); ?></h2>
					<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
						<a href='#' onclick='return jQuery.dds.selectAll("dep_codes");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectNone("dep_codes");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectInvert("dep_codes");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
					</p>
					<div>
						<ul id="dep_codes">
							<?php
								foreach ($dependent_codes as $mmsc)
									print '<li id='.$mmsc.'>'.MMSG_PREFIX.$mmsc.'</li>';
							?>
						</ul>
					</div>
				</div>	
			</div>
		<?php
			}
		?>
		<table class="widefat" style="width:95%">
			<tr>
				<th>
					<? _e('code dependencies', MMSG_PLUGIN_NAME); ?>
				</th>
				<th>
					<? _e('script dependencies', MMSG_PLUGIN_NAME); ?>
				</th>
				<th style="text-align:left;">
					<? _e('actions', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td>
					<input id="code_deps" type="text" name="codes" value="<?= $script->codes ?>" style="width:100%;" readonly />
					<p><? _e('(generated automatically on save or update of drag and drop fields)', MMSG_PLUGIN_NAME); ?></p>
				</td>
				<td>
					<input id="script_deps" type="text" name="dependencies" value="<?= $script->dependencies ?>" style="width:100%;" readonly />
					<p><? _e('(generated automatically on save or update of drag and drop fields)', MMSG_PLUGIN_NAME); ?></p>
				</td>
				<td class="submit" style="text-align:left;">
					<input type="submit" name="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<br />
	<a href="<?= MMSG_ADMIN_URL ?>list_scripts.php">&lt;&lt; <? _e('Back to scripts', MMSG_PLUGIN_NAME); ?></a>
</div>