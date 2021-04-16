<?php

class FTAGTheme extends Themelet {
	public function get_parent_editor_html(Image $image) {
		$html = "<tr>\n".
		        "	<th>Pages</th>\n".
		        "	<td>\n".
				$image->page_count.
		        "	<td>\n".
		        "</tr>\n";
		return $html;
	}
}
