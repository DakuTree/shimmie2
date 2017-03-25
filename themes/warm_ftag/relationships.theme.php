<?php

class CustomRelationshipsTheme extends RelationshipsTheme {
	public function get_parent_editor_html(Image $image) {
		global $user;
		$h_parent_id = $image->parent_id;
		$s_parent_id = $h_parent_id ?: "None.";

		$html = "<tr>\n".
		        "	<th>Parent</th>\n".
		        "	<td>\n".
		        (!$user->is_anonymous() ?
		            "		<span class='view' style='overflow: hidden; white-space: nowrap;'>{$s_parent_id}</span>\n".
		            "		<input class='edit' type='text' name='tag_edit__parent' type='number' value='{$h_parent_id}'>\n"
		        :
		            $s_parent_id
		        ).
		        "	<td>\n".
		        "</tr>\n";
		return $html;
	}
}
?>
