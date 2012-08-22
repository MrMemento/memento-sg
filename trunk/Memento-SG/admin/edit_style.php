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
		$media        = $_POST['media'];
		$type         = $_POST['type'];
		$codes        = $MMSG->clean_white($_POST['codes']);

		if (($_POST['action']) == 'new')
		{
			if (!empty($name) && !$MMSG->exists_style($name))
			{
				$id = $MMSG->add_style($name, $source, $dependencies, $version, $media, $type, $codes);

				$action = 'update';

				$MMSG->fade_msg(sprintf(__('Style %s added.', MMSG_PLUGIN_NAME), $name));

				$style = $MMSG->get_style($id);
			}
			else
			{
				if (empty($name))
				{
					$MMSG->fade_msg(__('Style name was empty, please enter a name!', MMSG_PLUGIN_NAME));
				}
				else
				{
					$MMSG->fade_msg(sprintf(__('Style with name %s already exists, please use another name, or edit the existing one!', MMSG_PLUGIN_NAME), $name));
				}

				$style               = new stdClass;
				$style->name         = $name;
				$style->source       = $source;
				$style->dependencies = $dependencies;
				$style->version      = $version;
				$style->media        = $media;
				$style->codes        = $codes;
			}
		}
		else
		{
			$MMSG->update_style($id, $name, $source, $dependencies, $version, $media, $type, $codes);

			$MMSG->fade_msg(sprintf(__('Style %s updated.', MMSG_PLUGIN_NAME), $name));

			$style = $MMSG->get_style($id);
		}
	}
	else
	{
		$id = intval($_GET['id']);

		if ($id == 0)
		{
			$action = 'new';
			$style  = new stdClass;
		}
		else
		{
			$action = "update";
			$style  = $MMSG->get_style($id);
			$type   = $style->type;
		}
	}
?>
<div class="wrap">
	<h2>Memento Media Shortcode Generator (MMSG) - Style manager</h2>
	<p>
		<?php
			if ($id != 0)
			{
				_e('Here you can modify the CSS stylesheets to be added to your HTML frontend\'s head. It uses <code>wp_enqueue_style</code> function, to avoid duplicate styles, use versioning and dependencies.<br/>Dependencies should be a comma separated string WITHOUT whitespaces, this will be converted to array with <code>preg_split</code>.<br/><a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_style">Further reference</a>', MMSG_PLUGIN_NAME);
	 		}
			else
			{
				_e('Here you can add CSS stylesheets to your HTML frontend\'s head. It uses <code>wp_enqueue_style</code> function, to avoid duplicate styles, use versioning and dependencies.<br/>Dependencies should be a comma separated string WITHOUT whitespaces, this will be converted to array with <code>preg_split</code>.<br/><a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_style">Further reference</a>', MMSG_PLUGIN_NAME);
			}
		?>
	</p>
	<form action="<?= MMSG_ADMIN_URL ?>list_styles.php" method="post">
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
					<? _e('media', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td>
					<input type="text" name="name" value="<?= $style->name ?>" style="width:100%;" />
				</td>
				<td>
					<input type="text" name="source" value="<?= stripslashes($style->source) ?>" style="width:100%;" />
				</td>
				<td>
					<input type="text" name="version" value="<?= $style->version ?>" style="width:100%;" />
				</td>
				<td>
					<select name="media">
						<?php
							foreach (array("all", "braille", "embossed", "handheld", "print", "projection", "screen", "speech", "tty", "tv") as $m) {
						?>
						<option value="<?= $m; ?>" <?= $style->media == $m ? 'selected' : '' ?>>
							<?= $m; ?>
						</option>
						<?php
							}
						?>
					</select>
				</td>
			</tr>
		</table>
		<?php
			if (!isset(MementoShortcodeGenerator::$styles))
				MementoShortcodeGenerator::$styles = $MMSG->list_styles();

			$available_styles = array();
			$dependent_styles = array();
			foreach (MementoShortcodeGenerator::$styles as $mmst)
			{
				if (is_object($mmst) && $mmst->name != $style->name)
				{
					if (preg_match('/'.$mmst->name.'/', $style->dependencies))
						$dependent_styles[] = $mmst->name;
					else
						$available_styles[] = $mmst->name;
				}
			}
		?>
		<br/>
		<div style="float: left;">
			<div class='dragdrop_panel'>
				<h2><? _e('Available Styles', MMSG_PLUGIN_NAME); ?></h2>
				<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
					<a href='#' onclick='return jQuery.dds.selectAll("avail_styles");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectNone("avail_styles");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectInvert("avail_styles");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
				</p>
				<div>
					<ul id="avail_styles">
						<?php
							foreach ($available_styles as $mmst)
								print '<li id='.$mmst.'>'.$mmst.'</li>';
						?>
					</ul>
				</div>
			</div>
			<div class='dragdrop_panel'>
				<h2><? _e('Dependent Styles', MMSG_PLUGIN_NAME); ?></h2>
				<p><? _e('Select', MMSG_PLUGIN_NAME); ?>
					<a href='#' onclick='return jQuery.dds.selectAll("dep_styles");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectNone("dep_styles");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
					<a href='#' onclick='return jQuery.dds.selectInvert("dep_styles");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
				</p>
				<div>
					<ul id="dep_styles">
						<?php
							foreach ($dependent_styles as $mmst)
								print '<li id='.$mmst.'>'.$mmst.'</li>';
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
						if (preg_match('/'.$mmsc->shortcode.'/', $style->codes))
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
						<a href='#' onclick='return jQuery.dds.selectAll("list_1");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectNone("list_1");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectInvert("list_1");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
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
						<a href='#' onclick='return jQuery.dds.selectAll("list_2");'><? _e('All', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectNone("list_2");'><? _e('None', MMSG_PLUGIN_NAME); ?></a> 
						<a href='#' onclick='return jQuery.dds.selectInvert("list_2");'><? _e('Invert', MMSG_PLUGIN_NAME); ?></a>
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
					<? _e('style dependencies', MMSG_PLUGIN_NAME); ?>
				</th>
				<th style="text-align:left;">
					<? _e('actions', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td>
					<input id="code_deps" type="text" name="codes" value="<?= $style->codes ?>" style="width:100%;" readonly />
					<p><? _e('(generated automatically on save or update of drag and drop fields)', MMSG_PLUGIN_NAME); ?></p>
				</td>
				<td>
					<input id="style_deps" type="text" name="dependencies" value="<?= $style->dependencies ?>" style="width:100%;" readonly />
					<p><? _e('(generated automatically on save or update of drag and drop fields)', MMSG_PLUGIN_NAME); ?></p>
				</td>
				<td class="submit" style="text-align:left;">
					<input type="submit" name="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<br />
	<a href="<?= MMSG_ADMIN_URL ?>list_styles.php">&lt;&lt; <? _e('Back to styles', MMSG_PLUGIN_NAME); ?></a>
</div>