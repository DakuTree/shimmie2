<?php
class Themelet extends BaseThemelet {
	public function build_thumb_html(Image $image) {
		global $config;

		$i_id = (int) $image->id;
		$h_view_link = make_link('post/view/'.$i_id);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$h_tags = strtolower($image->get_tag_list());

		$fn = substr($image->filename, 0, -4);

		$extArr = array_flip(array('swf', 'svg')); //List of thumbless filetypes
		if(!isset($extArr[$image->ext])){
			$tsize = get_thumbnail_size($image->width, $image->height);
		}else{
			//Use max thumbnail size if using thumbless filetype
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}

		$custom_classes = "";
		if(class_exists("Relationships")){
			if(property_exists('Image', 'parent_id') && $image->parent_id !== NULL){	$custom_classes .= "shm-thumb-has_parent ";	}
			if(property_exists('Image', 'has_children') && $image->has_children == TRUE){ $custom_classes .= "shm-thumb-has_child "; }
		}

		return "<div class='thumb shm-thumb'>".
		       "	<a href=\"ftag://{$fn}\" class='{$custom_classes}' data-tags='$h_tags' data-post-id='$i_id'>".
		       "		<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' src='$h_thumb_link'>".
		       "	</a>".
			   "	<div>".
			   "		<a href='$h_view_link' class='thumb shm-thumb' data-tags='$h_tags' data-post-id='$i_id'>$fn</a>".
			   "	</div>".
		       "</div>";
	}
}
?>
