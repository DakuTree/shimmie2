<?php

class ExtManagerTheme extends Themelet {
	/**
	 * @param Page $page
	 * @param ExtensionInfo[] $extensions
	 * @param bool $editable
	 */
	public function display_table(Page $page, /*array*/ $extensions, /*bool*/ $editable) {
		$h_en = $editable ? "<th>Enabled</th>" : "";
		$html = "
			".make_form(make_link("ext_manager/set"))."
				<script type='text/javascript'>
				$(document).ready(function() {
					$(\"#extensions\").find(\"> tbody > tr\").each(function() {
						if($(this).attr('data-required')) {
							if(!ext_is_enabled($(this).attr('data-required'))) {
								$(this).find('input[type=checkbox]').parent().css('background-color', 'rgba(255, 0, 0, 0.75)');
								$(this).find('input[type=checkbox]').attr('disabled', true);
								$(this).find('input[type=checkbox]').attr('title', 'This requires these extensions to run: '+$(this).attr('data-required'));

							}
						} else if($(this).attr('data-optional')) {
							if(!ext_is_enabled($(this).attr('data-optional'))) {
								$(this).find('input[type=checkbox]').parent().css('background-color', 'rgba(255, 100, 0, 0.75)');
								$(this).find('input[type=checkbox]').attr('title', 'This extension has optional features which use these extensions: '+$(this).attr('data-required'));
							}
						}
					});

					function ext_is_enabled(name) {
						var enabled = 0;

						var name_arr = name.split(',');
						name_arr.forEach(function(n) {
							enabled = enabled + $(\"#extensions\").find(\"> tbody > tr[data-ext=\"+n+\"] > td:first-of-type > input:checked\").length;
						});

						return enabled;
					}
				});
				</script>
				<table id='extensions' class='zebra sortable'>
					<thead>
						<tr>
							$h_en
							<th>Name</th>
							<th>Docs</th>
							<th>Description</th>
						</tr>
					</thead>
					<tbody>
		";
		foreach($extensions as $extension) {
			if(!$editable && $extension->visibility == "admin") continue;

			$h_name        = html_escape(empty($extension->name) ? $extension->ext_name : $extension->name);
			$h_description = html_escape($extension->description);
			$h_link        = make_link("ext_doc/".url_escape($extension->ext_name));
			$h_enabled     = ($extension->enabled === TRUE ? " checked='checked'" : ($extension->enabled === FALSE ? "" : " disabled checked='checked'"));
			$h_enabled_box = $editable ? "<td><input type='checkbox' name='ext_".html_escape($extension->ext_name)."'$h_enabled></td>" : "";
			$h_docs        = ($extension->documentation ? "<a href='$h_link'>■</a>" : ""); //TODO: A proper "docs" symbol would be preferred here.

			$h_required_ext = ($extension->required_extensions ? "data-required='".implode(",", $extension->required_extensions)."'" : "");
			$h_optional_ext = ($extension->optional_extensions ? "data-optional='".implode(",", $extension->optional_extensions)."'" : "");

			$html .= "
				<tr data-ext='{$extension->ext_name}' {$h_required_ext} {$h_optional_ext}>
					{$h_enabled_box}
					<td>{$h_name}</td>
					<td>{$h_docs}</td>
					<td style='text-align: left;'>{$h_description}</td>
				</tr>";
		}
		$h_set = $editable ? "<tfoot><tr><td colspan='5'><input type='submit' value='Set Extensions'></td></tr></tfoot>" : "";
		$html .= "
					</tbody>
					$h_set
				</table>
			</form>
		";

		$page->set_title("Extensions");
		$page->set_heading("Extensions");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Extension Manager", $html));
	}

	/*
	public function display_blocks(Page $page, $extensions) {
		global $user;
		$col_1 = "";
		$col_2 = "";
		foreach($extensions as $extension) {
			$ext_name = $extension->ext_name;
			$h_name = empty($extension->name) ? $ext_name : html_escape($extension->name);
			$h_email = html_escape($extension->email);
			$h_link = isset($extension->link) ?
					"<a href=\"".html_escape($extension->link)."\">Original Site</a>" : "";
			$h_doc = isset($extension->documentation) ?
					"<a href=\"".make_link("ext_doc/".html_escape($extension->ext_name))."\">Documentation</a>" : "";
			$h_author = html_escape($extension->author);
			$h_description = html_escape($extension->description);
			$h_enabled = $extension->enabled ? " checked='checked'" : "";
			$h_author_link = empty($h_email) ?
					"$h_author" :
					"<a href='mailto:$h_email'>$h_author</a>";

			$html = "
				<p><table border='1'>
					<tr>
						<th colspan='2'>$h_name</th>
					</tr>
					<tr>
						<td>By $h_author_link</td>
						<td width='25%'>Enabled:&nbsp;<input type='checkbox' name='ext_$ext_name'$h_enabled></td>
					</tr>
					<tr>
						<td style='text-align: left' colspan='2'>$h_description<p>$h_link $h_doc</td>
					</tr>
				</table>
			";
			if($n++ % 2 == 0) {
				$col_1 .= $html;
			}
			else {
				$col_2 .= $html;
			}
		}
		$html = "
			".make_form(make_link("ext_manager/set"))."
				".$user->get_auth_html()."
				<table border='0'>
					<tr><td width='50%'>$col_1</td><td>$col_2</td></tr>
					<tr><td colspan='2'><input type='submit' value='Set Extensions'></td></tr>
				</table>
			</form>
		";

		$page->set_title("Extensions");
		$page->set_heading("Extensions");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Extension Manager", $html));
	}
	*/

	public function display_doc(Page $page, ExtensionInfo $info) {
		$author = "";
		if($info->author) {
			if($info->email) {
				$author = "<br><b>Author:</b> <a href=\"mailto:".html_escape($info->email)."\">".html_escape($info->author)."</a>";
			}
			else {
				$author = "<br><b>Author:</b> ".html_escape($info->author);
			}
		}
		$version = ($info->version) ? "<br><b>Version:</b> ".html_escape($info->version) : "";
		$link = ($info->link) ? "<br><b>Home Page:</b> <a href=\"".html_escape($info->link)."\">Link</a>" : "";
		$doc = $info->documentation;
		$html = "
			<div style='margin: auto; text-align: left; width: 512px;'>
				$author
				$version
				$link
				<p>$doc
				<hr>
				<p><a href='".make_link("ext_manager")."'>Back to the list</a>
			</div>";

		$page->set_title("Documentation for ".html_escape($info->name));
		$page->set_heading(html_escape($info->name));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Documentation", $html));
	}
}

