<?php

class CustomTagEditTheme extends TagEditTheme {
	public function get_tag_editor_html(Image $image) {
		global $user;
		$h_tags = html_escape($image->get_tag_list());
		return "
			<tr>
				<th width='50px'>Tags</th>
				<td>
		".($user->can("edit_image_tag") ? "
					<input class='view' type='text' name='tag_edit__tags' value='$h_tags' class='autocomplete_tags' id='tag_editor'>
		" : "
					$h_tags
		")."
				</td>
			</tr>
		";
	}

	public function get_source_editor_html(Image $image) {
		global $user;
		$h_source = html_escape($image->get_source());
		$f_source = $this->format_source($image->get_source());
		return "
			<tr>
				<th>Source</th>
				<td>
		".($user->can("edit_image_source") ? "
					<span style='overflow: hidden; white-space: nowrap;'>$f_source</span>
					<input class='view' type='text' name='tag_edit__source' id='tag_edit__source_edit' value='$h_source' style='display: none;'>
		" : "
					<span style='overflow: hidden; white-space: nowrap;'>$f_source</span>
		")."
				</td>
			</tr>
		";
	}

	public function get_lock_editor_html(Image $image) {
		return "";
	}
}
?>
