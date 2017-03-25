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

		$tag_html_a = array(); //contains non-categorized tags
		$tag_html_b = array(); //contains categorized tags

		foreach($tag_infos as $row) {
			$split = $this->return_tag($row, $tag_category_dict);
			$category = $split[0];
			$tag_html = $split[1];
			if($category == " "){
				$tag_html_a[] = $tag_html;
			}else{
				$tag_html_b[] = $tag_html;
			}
			// $main_html .= $tag_html . '<br />';
		}

		if(count($tag_html_b) > -1) $tag_html_b[count($tag_html_b)-1] .= "<br />";

		$new_tag_html = array_merge($tag_html_b, $tag_html_a);

		$i = 0;
		$len = count($new_tag_html);
		foreach($new_tag_html as $tag_html){
			$i++;
			if($i == 11) $main_html .= "<div id='slider'>";
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

	public function return_tag($row, $tag_category_dict) {
		global $config;

		$display_html = '';
		$tag = $row['tag'];
		$h_tag = html_escape($tag);

		$tag_category_css = '';
		$tag_category_style = '';
		$h_tag_split = explode(':', html_escape($tag), 2);
		$category = ' ';

		// we found a tag, see if it's valid!
		if((count($h_tag_split) > 1) and array_key_exists($h_tag_split[0], $tag_category_dict)) {
			$category = $h_tag_split[0];
			$h_tag = $h_tag_split[1];
			$tag_category_css .= ' tag_category_'.$category;
			$tag_category_style .= 'style="color:'.html_escape($tag_category_dict[$category]['color']).';" ';
		}

		$h_tag_no_underscores = str_replace("_", " ", $h_tag);
		$count = $row['calc_count'];
		// if($n++) $display_html .= "\n<br/>";
		if(!is_null($config->get_string('info_link'))) {
			$link = str_replace('$tag', $tag, $config->get_string('info_link'));
			$display_html .= ' <a class="tag_info_link'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">?</a>';
		}
		$link = $this->tag_link($row['tag']);
		$display_html .= ' <a class="tag_name'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">'.$h_tag_no_underscores.'</a>';

		if($config->get_bool("tag_list_numbers")) {
			$display_html .= " <span class='tag_count'>$count</span>";
		}

		$display_html = "<span class='tag'>{$display_html}</span>";
		return array($category, $display_html);
	}
}
?>
