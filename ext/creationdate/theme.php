<?php

class CreationDateTheme extends Themelet {
	public function get_cdate_editor_html(Image $image) {
		global $user;
		$h_cdate = $image->creation_date;
		$s_cdate = $h_cdate ?: "Not set.";

		$html = "<tr>\n".
		        "	<th>Date</th>\n".
		        "	<td>\n".
		        (!$user->is_anonymous() ?
		            "		<span class='view' style='overflow: hidden; white-space: nowrap;'>{$s_cdate}</span>\n".
		            "		<input class='edit' type='text' name='tag_edit__cdate' type='number' value='{$h_cdate}'>\n"
		        :
		            $s_cdate
		        ).
		        "	<td>\n".
		        "</tr>\n";
		return $html;
	}
}

