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

	if (!isset($type) && !$_POST)
		$type = $_GET['type'];

	if ($_POST)
	{
		$id           = $_POST['id'];
		$type         = $_POST['type'];
		$code         = $MMSG->clean_string($_POST['code']);
		$dependencies = $MMSG->clean_white($_POST['dependencies']);

		if (($_POST['action']) == 'new')
		{
			if (!empty($code) && !$MMSG->exists_shortcode($code))
			{
				switch ($type)
				{
					case 'rich':
						$dependencies = $MMSG->parse_code_dependencies($_POST['content']);
						$id = $MMSG->add_shortcode($code, $_POST['content'], $dependencies, $type);
					break;
					case 'plain':
						$dependencies = $MMSG->parse_code_dependencies($_POST['value']);
						$id = $MMSG->add_shortcode($code, $_POST['value'], $dependencies, $type);
					break;
					case 'advanced':
						$id = $MMSG->add_shortcode($code, $_POST['newcontent'], $dependencies, $type);
					break;
				}
				$action = 'update';

				$MMSG->fade_msg(sprintf(__('Shortcode %s added.', MMSG_PLUGIN_NAME), '['.MMSG_PREFIX.$code.']'));

				$shortcode = $MMSG->get_shortcode($id);
			}
			else
			{
				if (empty($code))
				{
					$MMSG->fade_msg(__('Shortcode was empty, please enter a shortcode!', MMSG_PLUGIN_NAME));
				}
				else
				{
					$MMSG->fade_msg(sprintf(__('Shortcode %s already exists, please use another shortcode, or edit the existing one!', MMSG_PLUGIN_NAME), '['.MMSG_PREFIX.$code.']'));
				}

				$shortcode            = new stdClass;
				$shortcode->shortcode = $code;
				$shortcode->type      = $type;
				switch ($type) {
					case 'rich':
						$shortcode->value        = $_POST['content'];
						$shortcode->dependencies = $MMSG->parse_code_dependencies($_POST['content']);
					break;
					case 'plain':
						$shortcode->value        = $_POST['value'];
						$shortcode->dependencies = $MMSG->parse_code_dependencies($_POST['value']);
					break;
					case 'advanced':
						$shortcode->value = $_POST['newcontent'];
					break;
				}
			}
		}
		else
		{
			switch ($type) {
				case 'rich':
					$dependencies = $MMSG->parse_code_dependencies($_POST['content']);
					$MMSG->update_shortcode($id, $code, $_POST['content'], $dependencies, $type);
				break;
				case 'plain':
					$dependencies = $MMSG->parse_code_dependencies($_POST['value']);
					$MMSG->update_shortcode($id, $code, $_POST['value'], $dependencies, $type);
				break;
				case 'advanced':
					$MMSG->update_shortcode($id, $code, $_POST['newcontent'], $dependencies, $type);
				break;
			}
			

			$MMSG->fade_msg(sprintf(__('Shortcode %s updated.', MMSG_PLUGIN_NAME), '['.MMSG_PREFIX.$code.']'));

			$shortcode = $MMSG->get_shortcode($id);
		}
	}
	else
	{
		$id = intval($_GET['id']);

		if ($id == 0)
		{
			$action          = 'new';
			$shortcode       = new stdClass;
			$shortcode->type = $type;
		}
		else
		{
			$action    = "update";
			$shortcode = $MMSG->get_shortcode($id);
		}
	}
?>
<div class="wrap">
	<h2>Memento Media Shortcode Generator (MMSG)</h2>
	<p>
		<?php
			if ($id != 0)
			{
				echo sprintf(__('To use this shortode place %s into a page, post, or another generated shortcode. If your placing another shortcode inside of this one, be sure that the shortcode you\'ve included here does not include this shortcode.<br /><b>If a shortcode contains itself, you will have created an endless loop and any page or post using this shortcode will not load.</b>', MMSG_PLUGIN_NAME), '<code>['.MMSG_PREFIX. $shortcode->shortcode .']</code>');
	 		}
			else
			{
				echo __('Enter the shortcode and the content you\'d like associated with it. A shortcode is a quick and easy code that will automatically be replaced with text, images, or any other content you wish.');
			}
		?>
	</p>
	<form action="<?= MMSG_ADMIN_URL ?>list_codes.php" method="post">
		<input type="hidden" name="id" value="<?= $id ?>" />
		<input type="hidden" name="action" value="<?= $action ?>" />
		<input type="hidden" name="type" value="<?= $type ?>" />
		<table class="widefat" style="width:95%">
			<tr>
				<th>
					<? _e('shortcode', MMSG_PLUGIN_NAME); ?>
				</th>
				<th>
					<? _e('text', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td>
					<?= MMSG_PREFIX ?><input type="text" name="code" value="<?= $shortcode->shortcode ?>" />
					<br /><br />
					<?php
						if ($action != 'new')
						{
							_e('In a page or post:', MMSG_PLUGIN_NAME);
							?>
							<br /><code>[<?php echo MMSG_PREFIX.$shortcode->shortcode; ?>]</code>
							<br />
							<? _e('In a template:', MMSG_PLUGIN_NAME); ?>
							<br /><code>do_shortcode('[<?php echo MMSG_PREFIX.$shortcode->shortcode; ?>]');</code>
						<?php
					}
					?>
				</td>
				<td id="poststuff">
					<?php
						switch ($type)
						{
							case 'rich':
								the_editor(stripslashes($shortcode->value));
							break;

							case 'plain':
								echo '<textarea name="value" id="value" rows="24" cols="85">'.
									stripslashes($shortcode->value).'</textarea>';
							break;

							case 'advanced':
								echo '<textarea name="newcontent" id="newcontent" rows="24" cols="85">'.
									stripslashes($shortcode->value).'</textarea>';
							break;

							case 'plain':
							default:
								echo '<textarea name="value" id="value" rows="24" cols="85">'.
									stripslashes($shortcode->value).'</textarea>';
							break;
						}
					?>
				</td>
			</tr>
		</table>
		<?php
			if ($type == 'advanced')
			{
				if (!isset(MementoShortcodeGenerator::$shortcodes))
					MementoShortcodeGenerator::$shortcodes = $MMSG->list_shortcodes();

				$available_codes = array();
				$dependent_codes = array();
				foreach (MementoShortcodeGenerator::$shortcodes as $mmsc)
				{
					if (is_object($mmsc) && $mmsc->shortcode != $shortcode->shortcode)
					{
						if (preg_match('/'.$mmsc->shortcode.'/', $shortcode->dependencies))
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
				<th style="text-align:left;">
					<? _e('actions', MMSG_PLUGIN_NAME); ?>
				</th>
			</tr>
			<tr>
				<td style="width=85%;">
					<input id="code_deps" type="text" name="dependencies" value="<?= $shortcode->dependencies ?>" style="width:100%;" readonly />
					<p><? _e('(generated automatically on save or update of drag and drop fields)', MMSG_PLUGIN_NAME); ?></p>
				</td>
				<td class="submit" style="text-align:left;">
					<input type="submit" name="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<div>
		<a href="<?= MMSG_ADMIN_URL ?>list_codes.php">&lt;&lt; <? _e('Back to shortcodes', MMSG_PLUGIN_NAME); ?></a>
	</div>
</div>