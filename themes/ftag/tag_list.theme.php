<?php

class CustomTagListTheme extends TagListTheme {
	public function display_related_block(Page $page, $tag_infos) {
		global $config;

		if($config->get_string('tag_list_related_sort') == 'alphabetical') asort($tag_infos);

		if(class_exists('TagCategories')) {
			$this->tagcategories = new TagCategories;
			$tag_category_dict = $this->tagcategories->getKeyedDict();
		}
		else {
			$tag_category_dict = array();
		}
		$main_html = '';

		$i = 0;
		$len = count($tag_infos);
		foreach($tag_infos as $row) {
			$i++;
			if($i == 11) $main_html .= "<div id='slider'>";
			$split = $this->return_tag($row, $tag_category_dict);
			$category = $split[0];
			$tag_html = $split[1];
			$main_html .= $tag_html . '<br />';
			if($i == $len && $i >= 11){
				$main_html .= "</div>\n";
				$main_html .= "<div id='slideblock'><a href='#' id='slidetoggle'>Show remaining ".($i - 10)." tags...</a></div>";
			}elseif($i == $len && $i < 11){
				$main_html .= "<div style='padding-bottom: 6px;'></div>"; //This is ugly
			}
		}

		if($config->get_string('tag_list_image_type')=="tags") {
			$page->add_block(new Block("Tags", $main_html, "left", 10));
		}
		else {
			$page->add_block(new Block("Related Tags", $main_html, "left", 10));
		}
	}
}
?>
